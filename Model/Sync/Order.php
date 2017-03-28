<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Order
{
    /**
     * @var array
     */
    public $accounts = [];
    /**
     * @var string
     */
    public $apiUsername;
    /**
     * @var string
     */
    public $apiPassword;

    /**
     * Global number of orders.
     *
     * @var int
     */
    public $countOrders = 0;

    /**
     * @var array
     */
    public $orderIds;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory
     */
    public $contactResourceFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\OrderFactory
     */
    public $orderFactory;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    public $salesOrderFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\OrderFactory
     */
    public $connectorOrderFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\AccountFactory
     */
    public $accountFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    public $importerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory
     */
    public $orderResourceFactory;
    /**
     * @var array
     */
    public $guests = [];

    /**
     * Order constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Model\OrderFactory $orderFactory
     * @param \Dotdigitalgroup\Email\Model\Connector\AccountFactory $accountFactory
     * @param \Dotdigitalgroup\Email\Model\Connector\OrderFactory $connectorOrderFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory $orderResourceFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Sales\Model\OrderFactory $salesOrderFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Model\Connector\AccountFactory $accountFactory,
        \Dotdigitalgroup\Email\Model\Connector\OrderFactory $connectorOrderFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\OrderFactory $orderResourceFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->importerFactory       = $importerFactory;
        $this->orderFactory          = $orderFactory;
        $this->accountFactory        = $accountFactory;
        $this->connectorOrderFactory = $connectorOrderFactory;
        $this->contactResourceFactory= $contactFactory;
        $this->orderResourceFactory  = $orderResourceFactory;
        $this->helper                = $helper;
        $this->resource              = $resource;
        $this->salesOrderFactory     = $salesOrderFactory;
        $this->storeManager          = $storeManagerInterface;
    }

    /**
     * Initial sync the transactional data.
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync()
    {
        $response = ['success' => true, 'message' => 'Done.'];

        // Initialise a return hash containing results of our sync attempt
        $this->searchWebsiteAccounts();

        foreach ($this->accounts as $account) {
            $orders = $account->getOrders();
            $ordersForSingleSync = $account->getOrdersForSingleSync();
            $numOrders = count($orders);
            $numOrdersForSingleSync = count($ordersForSingleSync);
            $website = $account->getWebsites();
            $this->countOrders += $numOrders;
            $this->countOrders += $numOrdersForSingleSync;
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
            $this->orderResourceFactory->create()
                ->setImported($this->orderIds);

            unset($this->accounts[$account->getApiUsername()]);
        }

        /**
         * Add guest to contacts table.
         */
        if (!empty($this->guests)) {
            $this->contactResourceFactory->create()
                ->insertGuest($this->guests);
        }

        if ($this->countOrders) {
            $response['message'] = 'Orders updated ' . $this->countOrders;
        }

        return $response;
    }

    /**
     * Search the configuration data per website.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function searchWebsiteAccounts()
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
                // limit for orders included to sync
                $limit = $this->helper->getWebsiteConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
                    $website
                );
                //set account for later use
                if (! isset($this->accounts[$this->apiUsername])) {
                    $account = $this->accountFactory->create();
                    $account->setApiUsername($this->apiUsername);
                    $account->setApiPassword($this->apiPassword);
                    $this->accounts[$this->apiUsername] = $account;
                }
                $pendingOrders = $this->getPendingConnectorOrders($website, $limit);
                if (! empty($pendingOrders)) {
                    $this->accounts[$this->apiUsername]->setOrders($pendingOrders);
                }
                $this->accounts[$this->apiUsername]->setWebsites($website->getId());
                $modifiedOrders = $this->getModifiedOrders($website, $limit);
                if (! empty($modifiedOrders)) {
                    $this->accounts[$this->apiUsername]->setOrdersForSingleSync($modifiedOrders);
                }
            }
        }
    }

    /**
     * @param $website
     * @param int $limit
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

        $orders = $this->mappOrderData($orderCollection, $orderModel, $orders);

        return $orders;
    }

    /**
     * @param $website
     * @param $limit
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

        $orders = $this->mappOrderData($orderCollection, $orderModel, $orders);

        return $orders;
    }


    /**
     * @param $orderCollection
     * @param $orderModel
     * @param $orders
     * @return array
     */
    protected function mappOrderData($orderCollection, $orderModel, $orders)
    {
        $orderIds = $orderCollection->getColumnValues('order_id');

        //get the order collection
        $salesOrderCollection = $orderModel->getSalesOrdersWithIds($orderIds);

        foreach ($salesOrderCollection as $order) {
            if ($order->getId()) {
                $storeId = $order->getStoreId();
                $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

                /**
                 * Add guest to array to add to contacts table.
                 */
                if ($order->getCustomerIsGuest()
                    && $order->getCustomerEmail()
                ) {
                    //add guest to the list
                    if (!isset($this->guests[$order->getCustomerEmail()])) {
                        $this->guests[$order->getCustomerEmail()] = [
                            'email' => $order->getCustomerEmail(),
                            'website_id' => $websiteId,
                            'store_id' => $storeId,
                            'is_guest' => 1
                        ];
                    }
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
     * @param $ordersForSingleSync
     * @param $website
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
}
