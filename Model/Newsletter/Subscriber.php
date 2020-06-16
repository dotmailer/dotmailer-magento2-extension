<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Dotdigitalgroup\Email\Model\Sync\SyncInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Sync subscribers.
 */
class Subscriber implements SyncInterface
{
    const STATUS_SUBSCRIBED = 1;
    const STATUS_NOT_ACTIVE = 2;
    const STATUS_UNSUBSCRIBED = 3;
    const STATUS_UNCONFIRMED = 4;

    /**
     * @var mixed
     */
    private $start;

    /**
     * Global number of subscriber updated.
     *
     * @var int
     */
    private $countSubscribers = 0;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    private $contactFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollection;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $emailContactResource;

    /**
     * @var SubscriberWithSalesExporter
     */
    private $subscriberWithSalesExporter;

    /**
     * @var \Dotdigitalgroup\Email\Model\DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * @var SubscriberExporter
     */
    private $subscriberExporter;

    /**
     * Subscriber constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param SubscriberExporter $subscriberExporter
     * @param SubscriberWithSalesExporter $subscriberWithSalesExporter
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Dotdigitalgroup\Email\Model\Newsletter\SubscriberExporter $subscriberExporter,
        \Dotdigitalgroup\Email\Model\Newsletter\SubscriberWithSalesExporter $subscriberWithSalesExporter,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
    ) {
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->helper            = $helper;
        $this->contactFactory    = $contactFactory;
        $this->orderCollection   = $orderCollection;
        $this->subscriberExporter = $subscriberExporter;
        $this->subscriberWithSalesExporter = $subscriberWithSalesExporter;
        $this->emailContactResource = $contactResource;
        $this->timezone = $timezone;
    }

    /**
     * Sync subscribers and unsubscribes in EC
     */
    public function sync(\DateTime $from = null)
    {
        $this->runExport();
        $this->unsubscribe();
    }

    /**
     * @return array
     */
    public function runExport()
    {
        $response    = ['success' => true, 'message' => '----------- Subscribers sync ----------- : '];
        $storesSummary = '';
        $this->start = microtime(true);
        $stores = $this->helper->getStores(true);

        foreach ($stores as $store) {
            $websiteId = $store->getWebsiteId();
            //if subscriber is enabled and mapped
            $apiEnabled = $this->helper->isEnabled($websiteId);
            $addressBook = $this->helper->getSubscriberAddressBook($websiteId);
            $subscriberEnabled = $this->helper->isSubscriberSyncEnabled($websiteId);

            //enabled and mapped
            if ($apiEnabled && $addressBook && $subscriberEnabled) {
                //ready to start sync
                $numUpdated = $this->exportSubscribersPerStore($store);

                // show message for any number of customers
                if ($numUpdated) {
                    $storesSummary .= $store->getName() . ' (' . $numUpdated . ') --';
                }

                $this->countSubscribers += $numUpdated;
            }
        }

        $response['message'] .= gmdate('H:i:s', microtime(true) - $this->start) . ', ';
        $response['message'] .= $storesSummary;
        $response['message'] .= ' Total synced = ' . $this->countSubscribers;

        if ($this->countSubscribers) {
            $this->helper->log($response['message']);
        }

        return $response;
    }

    /**
     * Export subscribers per store.
     *
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @return int
     */
    public function exportSubscribersPerStore($store)
    {
        $updated = 0;
        $website = $store->getWebsite();
        $storeId = $store->getId();
        $limit = $this->helper->getSyncLimit($website->getId());
        $isSubscriberSalesDataEnabled = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ENABLE_SUBSCRIBER_SALES_DATA,
            $website
        );

        $emailContactModel = $this->contactFactory->create();
        $subscribersAreCustomers = $emailContactModel->getSubscribersToImport($storeId, $limit);
        $subscribersAreGuest = $emailContactModel->getSubscribersToImport($storeId, $limit, false);

        $subscribersGuestEmails = $subscribersAreGuest->getColumnValues('email');
        $subscribersCustomerEmails = $subscribersAreCustomers->getColumnValues('email');

        $guestsWithOrders = [];
        if ($isSubscriberSalesDataEnabled && ! empty($subscribersGuestEmails)) {
            $guestsWithOrders = $this->checkInSales($subscribersGuestEmails);
        }
        $guestsWithoutOrders = array_diff($subscribersGuestEmails, $guestsWithOrders);
        $emailsWithNoSaleData = array_merge($guestsWithoutOrders, $subscribersCustomerEmails);

        $subscribersWithNoSaleData = [];
        if (! empty($emailsWithNoSaleData)) {
            $subscribersWithNoSaleData = $emailContactModel
                ->getSubscribersToImportFromEmails($emailsWithNoSaleData);
        }
        if (! empty($subscribersWithNoSaleData)) {
            $updated += $this->subscriberExporter->exportSubscribers(
                $store,
                $subscribersWithNoSaleData
            );
        }

        $subscribersWithSaleData = [];
        if (! empty($guestsWithOrders)) {
            $subscribersWithSaleData = $emailContactModel->getSubscribersToImportFromEmails($guestsWithOrders);
        }

        if (! empty($subscribersWithSaleData)) {
            $updated += $this->subscriberWithSalesExporter->exportSubscribersWithSales(
                $store,
                $subscribersWithSaleData
            );
        }
        return $updated;
    }

    /**
     * Check emails exist in sales order table.
     *
     * @param array $emails
     *
     * @return array
     */
    public function checkInSales($emails)
    {
        return $this->orderCollection->create()
            ->checkInSales($emails);
    }

    /**
     * Un-subscribe suppressed contacts.
     *
     * @return array
     */
    public function unsubscribe()
    {
        $result['customers'] = 0;
        $suppressedEmails = [];

        /**
         * Sync all suppressed for each store
         */
        $websites = $this->helper->getWebsites(true);

        foreach ($websites as $website) {
            //not enabled
            if (! $this->helper->isEnabled($website)) {
                continue;
            }

            // add unique contact emails
            foreach ($this->helper->getSuppressedContacts($website) as $suppressedContact) {
                if (!array_key_exists($suppressedContact['email'], $suppressedEmails)) {
                    $suppressedEmails[$suppressedContact['email']] = [
                        'email' => $suppressedContact['email'],
                        'removed_at' => $suppressedContact['removed_at'],
                    ];
                }
            }
        }

        //Mark suppressed contacts
        if (! empty($suppressedEmails)) {
            $result['customers'] = $this->emailContactResource
                ->unsubscribeWithResubscriptionCheck(array_values($suppressedEmails));
        }
        return $result;
    }
}
