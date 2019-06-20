<?php

namespace Dotdigitalgroup\Email\Model\Sync;

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
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
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
        \Magento\Sales\Model\OrderFactory $salesOrderFactory
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

        // Initialise a return hash containing results of our sync attempt
        $this->searchWebsiteAccounts();

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

            unset($this->accounts[$account->getApiUsername()]);
        }

        /**
         * Add guests to contact table.
         */
        if (! empty($this->guests)) {
            $orderEmails = array_keys($this->guests);
            $guestsEmailFound = $this->contactCollectionFactory->create()
                ->addFieldToFilter('email', ['in' => $orderEmails])
                ->getColumnValues('email');
            //remove the contacts that already exists
            foreach ($guestsEmailFound as $email) {
                unset($this->guests[strtolower($email)]);
            }

            //insert new guests contacts
            $this->contactResource->insertGuests($this->guests);
            //mark the existing contacts with is guest in bulk
            $this->contactResource->updateContactsAsGuests($guestsEmailFound);
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
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return null
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
     * @param int $limit
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
     * @param int $limit
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
                    $this->guests[strtolower($email)] = [
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
}
