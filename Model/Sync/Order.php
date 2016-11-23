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
     * @var
     */
    public $orderIds;
    /**
     * @var
     */
    public $orderIdsForSingleSync;

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
    public $contactFactory;
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
     * @var array
     */
    public $guests = [];

    /**
     * Order constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Model\Connector\AccountFactory $accountFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\Connector\OrderFactory $connectorOrderFactory
     * @param \Dotdigitalgroup\Email\Model\OrderFactory $orderFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Sales\Model\OrderFactory $salesOrderFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\Connector\AccountFactory $accountFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\Connector\OrderFactory $connectorOrderFactory,
        \Dotdigitalgroup\Email\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $contactFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->importerFactory       = $importerFactory;
        $this->connectorOrderFactory = $connectorOrderFactory;
        $this->accountFactory        = $accountFactory;
        $this->salesOrderFactory     = $salesOrderFactory;
        $this->orderFactory          = $orderFactory;
        $this->contactFactory        = $contactFactory;
        $this->helper                = $helper;
        $this->storeManager          = $storeManagerInterface;
        $this->resource              = $resource;
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
        $this->_searchAccounts();

        foreach ($this->accounts as $account) {
            $orders = $account->getOrders();
            $orderIds = $account->getOrderIds();
            $ordersForSingleSync = $account->getOrdersForSingleSync();
            $orderIdsForSingleSync = $account->getOrderIdsForSingleSync();
            //@codingStandardsIgnoreStart
            $numOrdersForSingleSync = count($ordersForSingleSync);
            $website = $account->getWebsites();
            $numOrders = count($orders);
            //@codingStandardsIgnoreEnd
            $this->countOrders += $numOrders;
            $this->countOrders += $numOrdersForSingleSync;
            //send transactional for any number of orders set
            if ($numOrders) {
                $this->helper->log(
                    '--------- Order sync ---------- : ' . $numOrders
                );
                //queue order into importer
                $this->helper->error('orders', $orders);
                try {
                    $this->importerFactory->create()
                        ->registerQueue(
                            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS,
                            $orders,
                            \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                            $website[0]
                        );
                } catch (\Exception $e) {
                    $this->helper->debug((string)$e, []);
                }

                $this->_setImported($orderIds);

                $this->helper->log('----------end order sync----------');
            }

            if ($numOrdersForSingleSync) {
                $error = false;
                foreach ($ordersForSingleSync as $order) {
                    $this->helper->log(
                        '--------- register Order sync in single with importer ---------- : '
                    );

                    //register in queue with importer
                    $this->importerFactory->create()
                        ->registerQueue(
                            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS,
                            $order,
                            \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
                            $website[0]
                        );
                    $this->helper->log(
                        '----------end order sync in single----------'
                    );
                }
                //if no error then set imported
                if (!$error) {
                    $this->_setImported($orderIdsForSingleSync, true);
                }
            }
            unset($this->accounts[$account->getApiUsername()]);
        }

        /**
         * Add guest to contacts table.
         */
        if (!empty($this->guests)) {
            $this->contactFactory->create()
                ->insert($this->guests);
        }

        if ($this->countOrders) {
            $response['message'] = 'Orders updated '
                . $this->countOrders;
        }

        return $response;
    }

    /**
     * Search the configuration data per website.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _searchAccounts()
    {
        $this->orderIds              = [];
        $this->orderIdsForSingleSync = [];
        $websites                    = $this->helper->getWebsites(true);
        foreach ($websites as $website) {
            $apiEnabled = $this->helper->isEnabled($website);
            $storeIds = $website->getStoreIds();
            // api and order sync should be enabled, skip website with no store ids
            if ($apiEnabled
                && $this->helper->getWebsiteConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
                    $website
                ) && !empty($storeIds)
            ) {
                $this->apiUsername = $this->helper->getApiUsername($website);
                $this->apiPassword = $this->helper->getApiPassword($website);
                // limit for orders included to sync
                $limit = $this->helper->getWebsiteConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
                    $website
                );
                if (!isset($this->accounts[$this->apiUsername])) {
                    $account                            = $this->accountFactory->create()
                        ->setApiUsername($this->apiUsername)
                        ->setApiPassword($this->apiPassword);
                    $this->accounts[$this->apiUsername] = $account;
                }
                $this->accounts[$this->apiUsername]->setOrders(
                    $this->getConnectorOrders($website, $limit)
                );
                $this->accounts[$this->apiUsername]->setOrderIds(
                    $this->orderIds
                );
                $this->accounts[$this->apiUsername]->setWebsites(
                    $website->getId()
                );
                $this->accounts[$this->apiUsername]->setOrdersForSingleSync(
                    $this->getConnectorOrders($website, $limit, true)
                );
                $this->accounts[$this->apiUsername]->setOrderIdsForSingleSync(
                    $this->orderIdsForSingleSync
                );
            }
        }
    }

    /**
     * Get all orders to import.
     *
     * @param            $website
     * @param int        $limit
     * @param bool|false $modified
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConnectorOrders(
        $website,
        $limit = 100,
        $modified = false
    ) {
        $orders = [];
        $storeIds = $website->getStoreIds();
        $orderModel = $this->orderFactory->create();
        if (empty($storeIds)) {
            return [];
        }

        $orderStatuses = $this->helper->getConfigSelectedStatus($website);

        //any statuses found
        if ($orderStatuses) {
            if ($modified) {
                $orderCollection = $orderModel->getOrdersToImport(
                    $storeIds,
                    $limit,
                    $orderStatuses,
                    true
                );
            } else {
                $orderCollection = $orderModel->getOrdersToImport(
                    $storeIds,
                    $limit,
                    $orderStatuses
                );
            }
        } else {
            return [];
        }

        try {
            //email_order order ids
            $orderIds = $orderCollection->getColumnValues('order_id');

            //get the order collection
            $salesOrderCollection = $this->salesOrderFactory->create()
                ->getCollection()
                ->addFieldToFilter('entity_id', ['in' => $orderIds]);

            foreach ($salesOrderCollection as $order) {
                $storeId   = $order->getStoreId();
                $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
                /**
                 * Add guest to contacts table.
                 */
                if ($order->getCustomerIsGuest()
                    && $order->getCustomerEmail()
                ) {
                    //add guest to the list
                    $this->guests[] = [
                        'email' => $order->getCustomerEmail(),
                        'website_id' => $websiteId,
                        'store_id' => $storeId,
                        'is_guest' => 1
                    ];
                }
                if ($order->getId()) {
                    $connectorOrder = $this->connectorOrderFactory->create();
                    $connectorOrder->setOrderData($order);
                    $orders[] = $connectorOrder;
                }
                if ($modified) {
                    $this->orderIdsForSingleSync[] = $order->getId();
                } else {
                    $this->orderIds[] = $order->getId();
                }
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $orders;
    }

    /**
     * Set imported in bulk query.
     *
     * @param            $ids
     * @param bool|false $modified
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _setImported($ids, $modified = false)
    {
        try {
            $coreResource = $this->resource;
            $write = $coreResource->getConnection('core_write');
            $tableName = $coreResource->getTableName('email_order');
            $ids = implode(', ', $ids);

            if ($modified) {
                $write->update(
                    $tableName,
                    [
                        'modified' => 'null',
                        'updated_at' => gmdate('Y-m-d H:i:s')
                    ],
                    "order_id IN ($ids)"
                );
            } else {
                $write->update(
                    $tableName,
                    [
                        'email_imported' => 1,
                        'updated_at' => gmdate('Y-m-d H:i:s')
                    ],
                    "order_id IN ($ids)"
                );
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }
}
