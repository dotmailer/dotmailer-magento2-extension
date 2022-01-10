<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Connector\AccountHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterfaceFactory;
use Magento\Store\Api\StoreWebsiteRelationInterface;

class Unsubscriber extends DataObject
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
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

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
     * Subscriber constructor.
     *
     * @param Data $helper
     * @param Contact $contactResource
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param DateTimeFactory $dateTimeFactory
     * @param TimezoneInterfaceFactory $timezoneInterfaceFactory
     * @param AccountHandler $accountHandler
     * @param StoreWebsiteRelationInterface $storeWebsiteRelation
     * @param array $data
     */
    public function __construct(
        Data $helper,
        Contact $contactResource,
        ContactCollectionFactory $contactCollectionFactory,
        DateTimeFactory $dateTimeFactory,
        TimezoneInterfaceFactory $timezoneInterfaceFactory,
        AccountHandler $accountHandler,
        StoreWebsiteRelationInterface $storeWebsiteRelation,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->contactResource = $contactResource;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->timezoneInterfaceFactory = $timezoneInterfaceFactory;
        $this->accountHandler = $accountHandler;
        $this->storeWebsiteRelation = $storeWebsiteRelation;
        parent::__construct($data);
    }

    /**
     * Unsubscribe suppressed contacts by account.
     *
     * @param int $batchSize This argument enables unit testing of the while loop.
     *
     * @return int Count of unsubscribes.
     */
    public function unsubscribe($batchSize = self::BATCH_SIZE)
    {
        $unsubscribes = 0;

        $activeApiUsers = $this->accountHandler->getAPIUsersForECEnabledWebsites();
        if (!$activeApiUsers) {
            return 0;
        }

        foreach ($activeApiUsers as $apiUser) {
            $unsubscribes += $this->batchProcessSuppressions($apiUser['websites'], $batchSize);
        }

        return $unsubscribes;
    }

    /**
     * @param array $websiteIds
     * @param int $batchSize
     *
     * @return int
     */
    private function batchProcessSuppressions($websiteIds, $batchSize)
    {
        $skip = 0;
        $unsubscribes = 0;
        $client = $this->helper->getWebsiteApiClient($websiteIds[0]);

        do {
            $apiContacts = $client->getContactsSuppressedSinceDate(
                $this->getFromTime(),
                $batchSize,
                $skip
            );

            // no more contacts found or the api request failed
            if (!is_array($apiContacts)) {
                break;
            }

            $suppressedContacts = [];

            foreach ($apiContacts as $apiContact) {
                if (isset($apiContact->suppressedContact)) {
                    $suppressedContacts[] = [
                        'email' => $apiContact->suppressedContact->email,
                        'removed_at' => $apiContact->dateRemoved,
                    ];
                }
            }

            $unsubscribes += $this->unsubscribeWithResubscriptionCheck(
                $suppressedContacts,
                $websiteIds
            );

            $skip += $batchSize;
        } while (count($apiContacts) === $batchSize);

        return $unsubscribes;
    }

    /**
     * Process suppressions from EC, checking whether the user has resubscribed more recently in Magento
     *
     * @param array $suppressions
     * @param array $websiteIds
     * @return int
     */
    private function unsubscribeWithResubscriptionCheck(array $suppressions, $websiteIds)
    {
        if (empty($suppressions)) {
            return 0;
        }

        $localContacts = $this->contactCollectionFactory->create()
            ->getSubscribersWithScopeAndLastSubscribedAtDate(
                array_column($suppressions, 'email'),
                $websiteIds
            );
        $filteredContacts = $this->filterRecentlyResubscribedEmails($localContacts, $suppressions);

        // no emails to unsubscribe?
        if (empty($filteredContacts)) {
            return 0;
        }

        $scopeData = $this->getStoreIdsAndWebsiteIdsFromFilteredContacts($filteredContacts);

        return $this->contactResource->unsubscribeByWebsiteAndStore(
            array_column($filteredContacts, 'email'),
            $scopeData['websiteIds'],
            $scopeData['storeIds']
        );
    }

    /**
     * Filter out any unsubscribes from EC which have recently resubscribed in Magento
     *
     * @param array $localContacts
     * @param array $suppressions
     * @return array
     */
    private function filterRecentlyResubscribedEmails(array $localContacts, array $suppressions)
    {
        return array_filter(array_map(function ($contact) use ($suppressions) {
            // get corresponding suppression
            $contactKey = array_search($contact['email'], array_column($suppressions, 'email'));

            // if there is no last subscribed value, continue with unsubscribe
            if ($contactKey === false || $contact['last_subscribed_at'] === null) {
                return $contact;
            }

            // convert both timestamps to DateTime
            $lastSubscribedMagento = new \DateTime(
                $contact['last_subscribed_at'],
                new \DateTimeZone('UTC')
            );
            $removedAtEc = new \DateTime(
                $suppressions[$contactKey]['removed_at'],
                new \DateTimeZone('UTC')
            );

            // user recently resubscribed in Magento, do not unsubscribe them
            if ($lastSubscribedMagento > $removedAtEc) {
                return null;
            }
            return $contact;
        }, $localContacts));
    }

    /**
     * The filtered contacts may have only a subset of the account's website ids.
     * e.g.
     * - apiuser 1 has website ids 1 and 2
     * - email address is suppressed for account connected via apiuser 1
     * - but email resubscribed more recently on website id 2
     * - so unsubscribe only for website 1
     *
     * However, in newsletter_subscriber there may be subs for store ids that don't match email_contact,
     * because email_contact only stores one row per website. So we have to unsubscribe for ALL related
     * store ids.
     *
     * @param array $contacts
     * @return array
     */
    private function getStoreIdsAndWebsiteIdsFromFilteredContacts(array $contacts)
    {
        $data = [
            'websiteIds' => [],
            'storeIds' => []
        ];

        foreach ($contacts as $contact) {
            $websiteId = $contact['website_id'];
            if (!in_array($websiteId, $data['websiteIds'])) {
                $data['websiteIds'][] = $websiteId;
            }
            $related = $this->storeWebsiteRelation->getStoreByWebsiteId($websiteId);
            foreach ($related as $storeId) {
                if (!in_array($storeId, $data['storeIds'])) {
                    $data['storeIds'][] = $storeId;
                }
            }
        }

        return $data;
    }
}
