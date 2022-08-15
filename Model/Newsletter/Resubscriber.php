<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Connector\AccountHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterfaceFactory;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use Magento\Store\Api\StoreWebsiteRelationInterface;

class Resubscriber extends DataObject
{
    use SetsCronFromTime;

    const BATCH_SIZE = 1000;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Contact
     */
    private $contactResource;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var TimezoneInterfaceFactory
     */
    private $timezoneInterfaceFactory;

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
     * @param DateTimeFactory $dateTimeFactory
     * @param TimezoneInterfaceFactory $timezoneInterfaceFactory
     * @param AccountHandler $accountHandler
     * @param StoreWebsiteRelationInterface $storeWebsiteRelation
     * @param SubscriberFilterer $subscriberFilterer
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Contact $contactResource,
        DateTimeFactory $dateTimeFactory,
        TimezoneInterfaceFactory $timezoneInterfaceFactory,
        AccountHandler $accountHandler,
        StoreWebsiteRelationInterface $storeWebsiteRelation,
        SubscriberFilterer $subscriberFilterer,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->contactResource = $contactResource;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->timezoneInterfaceFactory = $timezoneInterfaceFactory;
        $this->accountHandler = $accountHandler;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->subscriberFilterer = $subscriberFilterer;
        parent::__construct($data);
    }

    /**
     * Subscribe by enabled account.
     *
     * @param int $batchSize This argument enables unit testing of the while loop.
     *
     * @return int
     */
    public function subscribe($batchSize = self::BATCH_SIZE)
    {
        $resubscribes = 0;

        $activeApiUsers = $this->accountHandler->getAPIUsersForECEnabledWebsites();
        if (!$activeApiUsers) {
            return 0;
        }

        foreach ($activeApiUsers as $apiUser) {
            $resubscribes += $this->batchProcessModifiedContacts($apiUser['websites'], $batchSize);
        }

        return $resubscribes;
    }

    /**
     * Loop through modified Dotdigital contacts and process resubscribes.
     *
     * @param array $websiteIds
     *
     * @param int $batchSize
     *
     * @return int
     * @throws \Exception
     */
    private function batchProcessModifiedContacts($websiteIds, $batchSize)
    {
        $skip = 0;
        $resubscribes = 0;
        $client = $this->helper->getWebsiteApiClient($websiteIds[0]);

        do {
            try {
                $apiContacts = $client->getContactsModifiedSinceDate(
                    $this->getFromTime(),
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

            $recentlySubscribedContacts = $this->filterModifiedContacts($apiContacts);

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
     *
     * @return array
     * @throws \Exception
     */
    private function filterModifiedContacts($contacts)
    {
        $recentlySubscribedContacts = [];

        $utcFromTime = new \DateTime(
            $this->getFromTime(),
            new \DateTimeZone('UTC')
        );

        foreach ($contacts as $contact) {
            $lastSubscribedAt = $this->getLastSubscribedAt($contact->dataFields);
            if (!$lastSubscribedAt) {
                continue;
            }

            $utcLastSubscribedAt = new \DateTime(
                $lastSubscribedAt,
                new \DateTimeZone('UTC')
            );

            if ($utcLastSubscribedAt < $utcFromTime) {
                continue;
            }

            $recentlySubscribedContacts[strtolower($contact->email)] = [
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

            if (!isset($contacts[strtolower($email)])) {
                continue;
            }

            // If change_status_at is empty, always resubscribe
            if (empty($subscriber->getChangeStatusAt())) {
                $filteredSubscribers[$subscriber->getStoreId()][] = [
                    'email' => $email
                ];
            // resubscribe if change_status_at is older
            } else {
                $utcChangeStatusAt = new \DateTime(
                    $subscriber->getChangeStatusAt(),
                    new \DateTimeZone('UTC')
                );

                // Note that 'subscribed_at' is already a date object
                if ($utcChangeStatusAt < $contacts[strtolower($email)]['subscribed_at']) {
                    $filteredSubscribers[$subscriber->getStoreId()][] = [
                        'email' => $email
                    ];
                }
            }
        }

        return $filteredSubscribers;
    }
}
