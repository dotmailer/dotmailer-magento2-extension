<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Dotdigital\V3\Models\DataFieldCollection;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Contact\ContactUpdaterInterface;
use Dotdigitalgroup\Email\Model\Cron\CronFromTimeSetter;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Api\StoreWebsiteRelationInterface;

class Resubscriber implements ContactUpdaterInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CronFromTimeSetter
     */
    private $cronFromTimeSetter;

    /**
     * @var Contact
     */
    private $contactResource;

    /**
     * @var StoreWebsiteRelationInterface
     */
    private $storeWebsiteRelation;

    /**
     * @var SubscriberFilterer
     */
    private $subscriberFilterer;

    /**
     * @param Logger $logger
     * @param CronFromTimeSetter $cronFromTimeSetter
     * @param Contact $contactResource
     * @param StoreWebsiteRelationInterface $storeWebsiteRelation
     * @param SubscriberFilterer $subscriberFilterer
     */
    public function __construct(
        Logger $logger,
        CronFromTimeSetter $cronFromTimeSetter,
        Contact $contactResource,
        StoreWebsiteRelationInterface $storeWebsiteRelation,
        SubscriberFilterer $subscriberFilterer
    ) {
        $this->logger = $logger;
        $this->cronFromTimeSetter = $cronFromTimeSetter;
        $this->contactResource = $contactResource;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        $this->subscriberFilterer = $subscriberFilterer;
    }

    /**
     * @inheritdoc
     */
    public function processBatch(array $batch, array $websiteIds)
    {
        $resubscribeCount = 0;

        try {
            $recentlySubscribedContacts = $this->filterModifiedContacts($batch);

            if (count($recentlySubscribedContacts) === 0) {
                return;
            }

            $resubscribeCount = $this->doResubscribes($recentlySubscribedContacts, $websiteIds);
        } catch (\Exception $e) {
            $this->logger->debug('Error processing batch', [(string) $e]);
        }

        if (!$resubscribeCount) {
            return;
        }

        $this->logger->info(
            sprintf(
                '%s contacts resubscribed in website ids %s',
                $resubscribeCount,
                implode(',', $websiteIds)
            )
        );
    }

    /**
     * Retrieve the LASTSUBSCRIBED data field value.
     *
     * @param DataFieldCollection|null $dataFields
     *
     * @return string|bool
     */
    private function getLastSubscribedAt(?DataFieldCollection $dataFields)
    {
        if (!$dataFields) {
            return false;
        }
        
        foreach ($dataFields->all() as $dataField) {
            if ($dataField->getKey() === 'LASTSUBSCRIBED') {
                return $dataField->getValue();
            }
        }

        return false;
    }

    /**
     * Filter modified contacts.
     *
     * Trim the set to include only contacts with an email identifier
     * and a recent LASTSUBSCRIBED data field.
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
            $this->cronFromTimeSetter->getFromTime(),
            new \DateTimeZone('UTC')
        );

        foreach ($contacts as $contact) {
            $lastSubscribedAt = $this->getLastSubscribedAt($contact->getDataFields());
            if (!$lastSubscribedAt || !$contact->getIdentifiers()->getEmail()) {
                continue;
            }

            $utcLastSubscribedAt = new \DateTime(
                $lastSubscribedAt,
                new \DateTimeZone('UTC')
            );

            if ($utcLastSubscribedAt < $utcFromTime) {
                continue;
            }

            $recentlySubscribedContacts[strtolower($contact->getIdentifiers()->getEmail())] = [
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
     * @throws LocalizedException
     */
    private function doResubscribes(array $contacts, array $websiteIds)
    {
        $matchingSubscribers = $this->subscriberFilterer->getSubscribersByEmailsStoresAndStatus(
            array_keys($contacts),
            $this->getStoreIdsFromWebsiteIds($websiteIds),
            Subscriber::STATUS_UNSUBSCRIBED
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
     * @return array
     * @throws \Exception
     */
    private function filterSubscribersByChangeStatusAt(SubscriberCollection $collection, array $contacts)
    {
        $filteredSubscribers = [];

        /** @var Subscriber $subscriber */
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
