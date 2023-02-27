<?php

namespace Dotdigitalgroup\Email\Model\Integration\Data;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Sync\Order\Exporter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as SalesOrderCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Orders
{
    private const NUMBER_OF_ORDERS = 10;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * @var SalesOrderCollectionFactory
     */
    private $salesOrderCollectionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Logger $logger
     * @param Data $helper
     * @param Exporter $exporter
     * @param SalesOrderCollectionFactory $salesOrderCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        Exporter $exporter,
        SalesOrderCollectionFactory $salesOrderCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->exporter = $exporter;
        $this->salesOrderCollectionFactory = $salesOrderCollectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Prepare data and send.
     *
     * @param int $websiteId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareAndSend(int $websiteId)
    {
        $salesOrderData = $this->getSalesOrderData($websiteId);
        if (empty($salesOrderData)) {
            $this->logger->debug(sprintf('No suitable orders found for website %d', $websiteId));
            return false;
        }

        $contactsSuccess = $this->sendContacts($salesOrderData, $websiteId);
        $ordersSuccess = $this->sendOrders($salesOrderData, $websiteId);

        return $contactsSuccess && $ordersSuccess;
    }

    /**
     * Create and filter a sales collection.
     *
     * @param int $websiteId
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getSalesOrderData($websiteId)
    {
        /** @var \Magento\Store\Model\Website $website */
        $website = $this->storeManager->getWebsite($websiteId);
        $storeIds = $website->getStoreIds();
        $onlySubscribers = $this->helper->isOnlySubscribersForContactSync($websiteId);
        $statuses = $this->getOrderStatuses($websiteId);

        return $this->getFilteredOrdersWithSubscriberData(
            self::NUMBER_OF_ORDERS,
            $storeIds,
            $statuses,
            $onlySubscribers
        );
    }

    /**
     * Get order statuses to be synced for scope.
     *
     * @param int $websiteId
     *
     * @return string[]
     */
    private function getOrderStatuses($websiteId)
    {
        $statuses = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_DATA_FIELDS_STATUS,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        return explode(',', $statuses ?: '');
    }

    /**
     * Fetch unprocessed orders, filtered by order status, with subscriber data.
     *
     * We only use this in the integration setup flow, where we can supply statuses for the
     * current website scope. In regular order sync, we get orders to process and filter in
     * the exporter. We use a query instead of getting a collection in order to use joinLeft
     * ($collection->join() joins INNER).
     *
     * @param int $limit
     * @param array $storeIds
     * @param array $statuses
     * @param bool $onlySubscribers
     *
     * @return array
     */
    private function getFilteredOrdersWithSubscriberData(
        int $limit,
        array $storeIds,
        array $statuses,
        bool $onlySubscribers
    ) {
        $collection = $this->salesOrderCollectionFactory->create();
        $connection = $collection->getResource()->getConnection();
        $select = $connection->select()
            ->from([
                'sales_order' => $collection->getMainTable()
            ])
            ->joinLeft(
                ['newsletter_subscriber' => $collection->getTable('newsletter_subscriber')],
                'newsletter_subscriber.subscriber_email = sales_order.customer_email'
            )
            ->where('sales_order.store_id IN (?)', $storeIds)
            ->order('sales_order.created_at DESC')
            ->limit($limit);

        if ($onlySubscribers) {
            $select->where('subscriber_status = ?', Subscriber::STATUS_SUBSCRIBED);
        }

        if (!empty($statuses)) {
            $select->where('sales_order.status in (?)', $statuses);
        }

        return $connection->fetchAll($select);
    }

    /**
     * Send contacts to Dotdigital.
     *
     * @param array $data
     * @param int $websiteId
     *
     * @return bool
     */
    private function sendContacts($data, $websiteId)
    {
        $customerAddressBookId = $this->helper->getCustomerAddressBook($websiteId);
        $guestAddressBookId = $this->helper->getGuestAddressBook($websiteId);
        $subscriberAddressBookId = $this->helper->getSubscriberAddressBook($websiteId);
        $client = $this->helper->getWebsiteApiClient($websiteId);
        $processed = [];

        foreach ($data as $row) {
            if (in_array($row['customer_email'], $processed)) {
                continue;
            }

            $addressBookId = $row['customer_id'] ? $customerAddressBookId : $guestAddressBookId;
            $contact = [
                'Email' => $row['customer_email'],
                'EmailType' => 'Html',
            ];
            $result = $client->postAddressBookContacts($addressBookId, $contact);
            if (isset($result->message)) {
                return false;
            }

            if ($row['subscriber_status'] == Subscriber::STATUS_SUBSCRIBED) {
                $result = $client->postAddressBookContacts($subscriberAddressBookId, $contact);
                if (isset($result->message)) {
                    return false;
                }
            }

            $processed[] = $row['customer_email'];
        }

        $this->logger->info(sprintf(
            '%d contacts posted for website %d',
            count($processed),
            $websiteId
        ));

        return true;
    }

    /**
     * Send orders to Dotdigital.
     *
     * We have to run a
     *
     * @param array $data
     * @param int $websiteId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendOrders($data, $websiteId)
    {
        $collection = $this->salesOrderCollectionFactory->create()
            ->addFieldToFilter('entity_id', ['in' => array_column($data, 'entity_id')]);

        $batch = $this->exporter->mapOrderData($collection);
        if (empty($batch) || !isset($batch[$websiteId])) {
            $this->logger->debug(sprintf('No order data was batched for website %d', $websiteId));
            return false;
        }

        $client = $this->helper->getWebsiteApiClient($websiteId);
        if (!$client) {
            $this->logger->debug(sprintf('API client error for website %d', $websiteId));
            return false;
        }

        $successes = [];
        foreach ($batch[$websiteId] as $order) {
            $result = $client->postContactsTransactionalData($order);
            if (!isset($result->message)) {
                $successes[] = $order['id'];
            }
        }

        $successCount = count($successes);

        if ($successCount === 0) {
            return false;
        }

        $this->logger->info(sprintf(
            '%d orders posted for website %d',
            $successCount,
            $websiteId
        ));

        return true;
    }
}
