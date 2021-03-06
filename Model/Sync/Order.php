<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Sync Orders.
 */
class Order implements SyncInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory
     */
    public $contactCollectionFactory;

    /**
     * @var array
     */
    private $accounts = [];

    /**
     * @var string
     */
    private $apiUsername;

    /**
     * @var string
     */
    private $apiPassword;

    /**
     * Global number of orders.
     *
     * @var array
     */
    public $countOrders = [
        'orders' => 0,
        'single_sync' => 0,
        'pending' => 0,
        'modified' => 0,
    ];

    /**
     * @var array
     */
    private $orderIds = [];

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contactResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $salesOrderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\OrderFactory
     */
    private $connectorOrderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\AccountFactory
     */
    private $accountFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order
     */
    private $orderResource;

    /**
     * @var array
     */
    public $guests = [];

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    private $catalogResource;

    /**
     * @var \Dotdigitalgroup\Email\Model\Catalog\UpdateCatalogBulk
     */
    private $bulkUpdate;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Order constructor.
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Model\OrderFactory $orderFactory
     * @param \Dotdigitalgroup\Email\Model\Connector\AccountFactory $accountFactory
     * @param \Dotdigitalgroup\Email\Model\Connector\OrderFactory $connectorOrderFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order $orderResource
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Sales\Model\OrderFactory $salesOrderFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource
     * @param \Dotdigitalgroup\Email\Model\Catalog\UpdateCatalogBulk $bulkUpdate
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Model\Connector\AccountFactory $accountFactory,
        \Dotdigitalgroup\Email\Model\Connector\OrderFactory $connectorOrderFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory $contactCollectionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order $orderResource,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource,
        \Dotdigitalgroup\Email\Model\Catalog\UpdateCatalogBulk $bulkUpdate,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->importerFactory       = $importerFactory;
        $this->orderFactory          = $orderFactory;
        $this->accountFactory        = $accountFactory;
        $this->connectorOrderFactory = $connectorOrderFactory;
        $this->contactResource       = $contactResource;
        $this->orderResource         = $orderResource;
        $this->helper                = $helper;
        $this->salesOrderFactory     = $salesOrderFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->catalogResource = $catalogResource;
        $this->bulkUpdate = $bulkUpdate;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Initial sync the transactional data.
     *
     * @return array
     *
     * @param \DateTime|null $from
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync(\DateTime $from = null)
    {
        $response = ['success' => true, 'message' => 'Done.'];

        $limit = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );

        // Initialise a return hash containing results of our sync attempt
        $this->searchWebsiteAccounts($limit);

        foreach ($this->accounts as $account) {
            $orders = $account->getOrders();
            $ordersForSingleSync = $account->getOrdersForSingleSync();
            $numOrders = count($orders);
            $numOrdersForSingleSync = count($ordersForSingleSync);
            $website = $account->getWebsites();

            $this->countOrders['orders'] += $numOrders;
            $this->countOrders['single_sync'] += $numOrdersForSingleSync;

            //create bulk
            if ($numOrders) {
                $this->helper->log('--------- Order sync ---------- : ' . $numOrders);
                //queue order into importer
                $this->importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS,
                        $orders,
                        \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                        $website[0]
                    );
            }
            //create single
            if ($numOrdersForSingleSync) {
                $this->createSingleImports($ordersForSingleSync, $website);
            }

            //mark the orders as imported
            $this->orderResource->setImported($this->orderIds);

            //Mark ordered products as unprocessed to be imported again
            $mergedProducts = $this->getAllProducts($orders + $ordersForSingleSync);
            $this->bulkUpdate->execute($mergedProducts);

            unset($this->accounts[$account->getApiUsername()]);
        }

        /**
         * Add guests to contact table.
         */
        if (!empty($this->guests)) {
            $guestsToInsert = [];

            foreach ($this->guests as $websiteId => $guests) {
                $guestEmails = array_keys($guests);

                $matchingContacts = $this->contactCollectionFactory->create()
                    ->addFieldToFilter('email', ['in' => $guestEmails])
                    ->addFieldToFilter('website_id', $websiteId)
                    ->getColumnValues('email');

                foreach (array_diff($guestEmails, $matchingContacts) as $email) {
                    $guestsToInsert[] = $this->guests[$websiteId][strtolower($email)];
                }

                //mark existing contacts with is_guest, by website
                $this->contactResource->updateContactsAsGuests($matchingContacts, $websiteId);
            }

            //insert new guest contacts
            $this->contactResource->insertGuests($guestsToInsert);
        }

        $totalOrders = $this->countOrders['orders'] + $this->countOrders['single_sync'];
        if ($this->countOrders) {
            $response = [
                'message' => 'Orders updated ' . $totalOrders,
            ] + $this->countOrders + $response;
        }

        return $response;
    }

    /**
     * Search the configuration data per website.
     *
     * @param string $limit
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function searchWebsiteAccounts($limit)
    {
        $websites = $this->helper->getWebsites();
        foreach ($websites as $website) {
            $apiEnabled = $this->helper->isEnabled($website);
            $storeIds = $website->getStoreIds();
            // api and order sync should be enabled, skip website with no store ids
            if ($apiEnabled && $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
                $website
            )
                && !empty($storeIds)
            ) {
                $this->apiUsername = $this->helper->getApiUsername($website);
                $this->apiPassword = $this->helper->getApiPassword($website);

                //set account for later use
                if (! isset($this->accounts[$this->apiUsername])) {
                    $account = $this->accountFactory->create();
                    $account->setApiUsername($this->apiUsername);
                    $account->setApiPassword($this->apiPassword);
                    $this->accounts[$this->apiUsername] = $account;
                }

                $pendingOrders = $this->getPendingConnectorOrders($website, $limit);
                if (! empty($pendingOrders)) {
                    $this->countOrders['pending'] += count($pendingOrders);
                    $this->accounts[$this->apiUsername]->setOrders($pendingOrders);
                }
                $this->accounts[$this->apiUsername]->setWebsites($website->getId());

                $modifiedOrders = $this->getModifiedOrders($website, $limit);
                if (! empty($modifiedOrders)) {
                    $this->countOrders['modified'] += count($modifiedOrders);
                    $this->accounts[$this->apiUsername]->setOrdersForSingleSync($modifiedOrders);
                }
            }
        }
    }

    /**
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param string|int $limit
     *
     * @return array
     */
    public function getPendingConnectorOrders($website, $limit = 100)
    {
        $orders = [];
        $storeIds = $website->getStoreIds();
        /** @var \Dotdigitalgroup\Email\Model\Order $orderModel */
        $orderModel = $this->orderFactory->create();
        //get order statuses set in configuration section
        $orderStatuses = $this->helper->getConfigSelectedStatus($website);

        //no active store for website
        if (empty($storeIds) || empty($orderStatuses)) {
            return [];
        }

        //pending order from email_order
        $orderCollection = $orderModel->getOrdersToImport($storeIds, $limit, $orderStatuses);

        //no orders found
        if (! $orderCollection->getSize()) {
            return $orders;
        }

        $orders = $this->mapOrderData($orderCollection, $orderModel, $orders);

        return $orders;
    }

    /**
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param string|int $limit
     *
     * @return array
     */
    protected function getModifiedOrders($website, $limit)
    {
        $orders =  [];
        $storeIds = $website->getStoreIds();
        /** @var \Dotdigitalgroup\Email\Model\Order $orderModel */
        $orderModel = $this->orderFactory->create();
        //get order statuses set in configuration section
        $orderStatuses = $this->helper->getConfigSelectedStatus($website);

        //no active store for website
        if (empty($storeIds) || empty($orderStatuses)) {
            return [];
        }

        //pending order from email_order
        $orderCollection = $orderModel->getModifiedOrdersToImport($storeIds, $limit, $orderStatuses);

        //no orders found
        if (! $orderCollection->getSize()) {
            return $orders;
        }

        $orders = $this->mapOrderData($orderCollection, $orderModel, $orders);

        return $orders;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection $orderCollection
     * @param \Dotdigitalgroup\Email\Model\Order $orderModel
     * @param array $orders
     *
     * @return array
     */
    protected function mapOrderData($orderCollection, $orderModel, $orders)
    {
        $orderIds = $orderCollection->getColumnValues('order_id');

        //get the order collection
        $salesOrderCollection = $orderModel->getSalesOrdersWithIds($orderIds);

        foreach ($salesOrderCollection as $order) {
            if ($order->getId()) {
                $storeId = $order->getStoreId();
                $websiteId = $this->helper->storeManager->getStore($storeId)->getWebsiteId();

                /**
                 * Add guest to array to add to contacts table.
                 */
                if ($order->getCustomerIsGuest() && $order->getCustomerEmail()) {
                    $email = $order->getCustomerEmail();
                    $this->guests[$websiteId][strtolower($email)] = [
                        'email' => $email,
                        'website_id' => $websiteId,
                        'store_id' => $storeId,
                        'is_guest' => 1
                    ];
                }

                $connectorOrder = $this->connectorOrderFactory->create();
                $connectorOrder->setOrderData($order);
                $orders[] = $connectorOrder;
            }

            $this->orderIds[] = $order->getId();
        }

        return $orders;
    }

    /**
     * @param array $ordersForSingleSync
     * @param array $website
     *
     * @return null
     */
    protected function createSingleImports($ordersForSingleSync, $website)
    {
        foreach ($ordersForSingleSync as $order) {
            //register in queue with importer
            $this->importerFactory->create()
                ->registerQueue(
                    \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS,
                    $order,
                    \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
                    $website[0]
                );
        }
    }

    /**
     * @param array $orders
     * @return array
     */
    private function getAllProducts($orders)
    {
        $allProducts = [];
        foreach ($orders as $order) {
            if (!isset($order['products'])) {
                continue;
            }
            foreach ($order['products'] as $products) {
                $allProducts[] = $products;
            }
        }
        return $allProducts;
    }
}
