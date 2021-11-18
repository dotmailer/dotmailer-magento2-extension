<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Connector\AccountHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use Magento\Store\Api\StoreWebsiteRelationInterface;

class Resubscriber
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Contact
     */
    private $contactResource;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var AccountHandler
     */
    private $accountHandler;

    /**
     * @var StoreWebsiteRelationInterface
     */
    private $storeWebsiteRelation;

    /**
     * @var SubscriberFilterer
     */
    private $subscriberFilterer;

    /**
     * @param Data $helper
     * @param Contact $contactResource
     * @param TimezoneInterface $timezone
     * @param AccountHandler $accountHandler
     * @param StoreWebsiteRelationInterface $storeWebsiteRelation
     * @param SubscriberFilterer $subscriberFilterer
     */
    public function __construct(
        Data $helper,
        Contact $contactResource,
        TimezoneInterface $timezone,
        AccountHandler $accountHandler,
        StoreWebsiteRelationInterface $storeWebsiteRelation,
        SubscriberFilterer $subscriberFilterer
    ) {
        $this->helper = $helper;
        $this->contactResource = $contactResource;
        $this->timezone = $timezone;
        $this->accountHandler = $accountHandler;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->subscriberFilterer = $subscriberFilterer;
    }

    /**
     * Subscribe by enabled account.
     *
     * @return int
     */
    public function subscribe()
    {
        $resubscribes = 0;

        $activeApiUsers = $this->accountHandler->getAPIUsersForECEnabledWebsites();
        if (!$activeApiUsers) {
            return 0;
        }

        foreach ($activeApiUsers as $apiUser) {
            $resubscribes += $this->batchProcessModifiedContacts($apiUser['websites']);
        }

        return $resubscribes;
    }

    /**
     * Loop through modified Dotdigital contacts and process resubscribes.
     *
     * @param array $websiteIds
     * @param string $intervalSpec
     * @return int
     */
    private function batchProcessModifiedContacts($websiteIds, $intervalSpec = 'PT24H')
    {
        $skip = 0;
        $batchSize = 1000;
        $resubscribes = 0;
        $date = $this->timezone->date()->sub(new \DateInterval($intervalSpec));
        $dateString = $date->format(\DateTime::ATOM);
        $client = $this->helper->getWebsiteApiClient($websiteIds[0]);

        do {
            try {
                $apiContacts = $client->getContactsModifiedSinceDate(
                    $dateString,
                    'true',
                    $batchSize,
                    $skip
                );
            } catch (\Exception $e) {
                break;
            }

            if (!is_array($apiContacts)) {
                break;
            }

            $recentlySubscribedContacts = $this->filterModifiedContacts($apiContacts, $dateString);

            if (count($recentlySubscribedContacts) === 0) {
                $skip += $batchSize;
                continue;
            }

            $resubscribes += $this->doResubscribes($recentlySubscribedContacts, $websiteIds);

            $skip += $batchSize;
        } while (count($apiContacts) === $batchSize);

        return $resubscribes;
    }

    /**
     * Retrieve the LASTSUBSCRIBED data field value.
     *
     * @param array $dataFields
     *
     * @return string|bool
     */
    private function getLastSubscribedAt(array $dataFields)
    {
        foreach ($dataFields as $dataField) {
            if ($dataField->key === 'LASTSUBSCRIBED') {
                return $dataField->value;
            }
        }

        return false;
    }

    /**
     * Trim the set to include only contacts with a recent subscribed date.
     *
     * @param Object[] $contacts
     * @param string $date
     *
     * @return array
     * @throws \Exception
     */
    private function filterModifiedContacts($contacts, $date)
    {
        $recentlySubscribedContacts = [];

        foreach ($contacts as $contact) {
            $lastSubscribedAt = $this->getLastSubscribedAt($contact->dataFields);
            if (!$lastSubscribedAt) {
                continue;
            }

            // convert both timestamps to DateTime
            $utcLastSubscribedAt = new \DateTime(
                $lastSubscribedAt,
                new \DateTimeZone('UTC')
            );
            $utcDateString = new \DateTime(
                $date,
                new \DateTimeZone('UTC')
            );

            if ($utcLastSubscribedAt < $utcDateString) {
                continue;
            }

            $recentlySubscribedContacts[$contact->email] = [
                'subscribed_at' => $utcLastSubscribedAt,
            ];
        }

        return $recentlySubscribedContacts;
    }

    /**
     * Find matching subscribers, and update them.
     *
     * @param array $contacts An array of contacts who subscribed in Dotdigital in the last X
     * @param array $websiteIds
     *
     * @return int
     */
    private function doResubscribes(array $contacts, array $websiteIds)
    {
        $matchingSubscribers = $this->subscriberFilterer->getSubscribersByEmailsStoresAndStatus(
            array_keys($contacts),
            $this->getStoreIdsFromWebsiteIds($websiteIds),
            \Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED
        );

        return $this->contactResource->subscribeByEmailAndStore(
            $this->filterSubscribersByChangeStatusAt($matchingSubscribers, $contacts)
        );
    }

    /**
     * Extract store ids related to website ids.
     *
     * @param array $websiteIds
     *
     * @return array
     */
    private function getStoreIdsFromWebsiteIds(array $websiteIds)
    {
        $storeIds = [];
        foreach ($websiteIds as $websiteId) {
            $related = $this->storeWebsiteRelation->getStoreByWebsiteId($websiteId);
            foreach ($related as $storeId) {
                $storeIds[] = $storeId;
            }
        }
        return $storeIds;
    }

    /**
     * Filter out any subscribers whose status changed more recently.
     *
     * @param SubscriberCollection $collection
     * @param array $contacts
     *
     * @throws \Exception
     */
    private function filterSubscribersByChangeStatusAt(SubscriberCollection $collection, array $contacts)
    {
        $filteredSubscribers = [];

        /** @var \Magento\Newsletter\Model\Subscriber $subscriber */
        foreach ($collection as $subscriber) {
            $email = $subscriber->getSubscriberEmail();

            $utcChangeStatusAt = new \DateTime(
                $subscriber->getChangeStatusAt(),
                new \DateTimeZone('UTC')
            );

            // Note that 'subscribed_at' is already a date object
            if ($utcChangeStatusAt < $contacts[$email]['subscribed_at']) {
                $filteredSubscribers[$subscriber->getStoreId()][] = [
                    'email' => $email
                ];
            }
        }

        return $filteredSubscribers;
    }
}
