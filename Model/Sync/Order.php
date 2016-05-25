<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Order
{
    /**
     * @var array
     */
    protected $accounts = [];
    /**
     * @var string
     */
    protected $_apiUsername;
    /**
     * @var string
     */
    protected $_apiPassword;

    /**
     * Global number of orders.
     *
     * @var int
     */
    protected $_countOrders = 0;

    /**
     * @var
     */
    protected $_orderIds;
    /**
     * @var
     */
    protected $_orderIdsForSingleSync;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    protected $_contactFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\OrderFactory
     */
    protected $_orderFactory;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_salesOrderFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\OrderFactory
     */
    protected $_connectorOrderFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Connector\AccountFactory
     */
    protected $_accountFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    protected $_importerFactory;

    /**
     * Order constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory          $importerFactory
     * @param \Dotdigitalgroup\Email\Model\Connector\AccountFactory $accountFactory
     * @param \Magento\Sales\Model\OrderFactory                     $salesOrderFactory
     * @param \Dotdigitalgroup\Email\Model\Connector\OrderFactory   $connectorOrderFactory
     * @param \Dotdigitalgroup\Email\Model\OrderFactory             $orderFactory
     * @param \Dotdigitalgroup\Email\Model\ContactFactory           $contactFactory
     * @param \Magento\Framework\App\ResourceConnection             $resource
     * @param \Dotdigitalgroup\Email\Helper\Data                    $helper
     * @param \Magento\Store\Model\StoreManagerInterface            $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\Connector\AccountFactory $accountFactory,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Dotdigitalgroup\Email\Model\Connector\OrderFactory $connectorOrderFactory,
        \Dotdigitalgroup\Email\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_importerFactory = $importerFactory;
        $this->_connectorOrderFactory = $connectorOrderFactory;
        $this->_accountFactory = $accountFactory;
        $this->_salesOrderFactory = $salesOrderFactory;
        $this->_orderFactory = $orderFactory;
        $this->_contactFactory = $contactFactory;
        $this->_helper = $helper;
        $this->_storeManager = $storeManagerInterface;
        $this->_resource = $resource;
    }

    /**
     * initial sync the transactional data.
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
            $numOrdersForSingleSync = count($ordersForSingleSync);
            $website = $account->getWebsites();
            $numOrders = count($orders);
            $this->_countOrders += $numOrders;
            $this->_countOrders += $numOrdersForSingleSync;
            //send transactional for any number of orders set
            if ($numOrders) {
                $this->_helper->log(
                    '--------- register Order sync with importer ---------- : '
                    .count($orders)
                );
                //register in queue with importer
                //$this->_helper->debug('orders', $orders);
                $this->_helper->error('orders', $orders);
                try {
                    $this->_importerFactory->create()
                        ->registerQueue(
                            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS,
                            $orders,
                            \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                            $website[0]
                        );
                } catch (\Exception $e) {
                    $this->_helper->debug((string) $e, []);
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($e->getMessage())
                    );
                }

                $this->_setImported($orderIds);

                $this->_helper->log('----------end order sync----------');
            }

            if ($numOrdersForSingleSync) {
                $error = false;
                foreach ($ordersForSingleSync as $order) {
                    $this->_helper->log(
                        '--------- register Order sync in single with importer ---------- : '
                        .$order->id
                    );
                    //register in queue with importer
                    $this->_importerFactory->create()
                        ->registerQueue(
                            \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_ORDERS,
                            $order,
                            \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE,
                            $website[0]
                        );
                    $this->_helper->log(
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

        if ($this->_countOrders) {
            $response['message'] = 'Number of updated orders : '
                .$this->_countOrders;
        }

        return $response;
    }

    /**
     * Search the configuration data per website.
     * 
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _searchAccounts()
    {
        $this->_orderIds = [];
        $websites = $this->_helper->getWebsites(true);
        foreach ($websites as $website) {
            $apiEnabled = $this->_helper->isEnabled($website);
            $storeIds = $website->getStoreIds();
            // api and order sync should be enabled, skip website with no store ids
            if ($apiEnabled
                && $this->_helper->getWebsiteConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
                    $website) && !empty($storeIds)
            ) {
                $this->_apiUsername = $this->_helper->getApiUsername($website);
                $this->_apiPassword = $this->_helper->getApiPassword($website);
                // limit for orders included to sync
                $limit = $this->_helper->getWebsiteConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT,
                    $website
                );
                if (!isset($this->accounts[$this->_apiUsername])) {
                    $account = $this->_accountFactory->create()
                        ->setApiUsername($this->_apiUsername)
                        ->setApiPassword($this->_apiPassword);
                    $this->accounts[$this->_apiUsername] = $account;
                }
                $this->accounts[$this->_apiUsername]->setOrders(
                    $this->getConnectorOrders($website, $limit)
                );
                $this->accounts[$this->_apiUsername]->setOrderIds(
                    $this->_orderIds
                );
                $this->accounts[$this->_apiUsername]->setWebsites(
                    $website->getId()
                );
                $this->accounts[$this->_apiUsername]->setOrdersForSingleSync(
                    $this->getConnectorOrders($website, $limit, true)
                );
                $this->accounts[$this->_apiUsername]->setOrderIdsForSingleSync(
                    $this->_orderIdsForSingleSync
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
        $orderModel = $this->_orderFactory->create();
        if (empty($storeIds)) {
            return [];
        }

        $orderStatuses = $this->_helper->getConfigSelectedStatus($website);

        //any statuses found
        if ($orderStatuses) {
            if ($modified) {
                $orderCollection = $orderModel->getOrdersToImport(
                    $storeIds, $limit, $orderStatuses, true
                );
            } else {
                $orderCollection = $orderModel->getOrdersToImport(
                    $storeIds, $limit, $orderStatuses
                );
            }
        } else {
            return [];
        }

        foreach ($orderCollection as $order) {
            try {
                $salesOrder = $this->_salesOrderFactory->create()->load(
                    $order->getOrderId()
                );
                $storeId = $order->getStoreId();
                $websiteId = $this->_storeManager->getStore($storeId)
                    ->getWebsiteId();
                /*
                 * Add guest to contacts table.
                 */
                if ($salesOrder->getCustomerIsGuest()) {
                    $this->_createGuestContact(
                        $salesOrder->getCustomerEmail(), $websiteId, $storeId
                    );
                }
                if ($salesOrder->getId()) {
                    $connectorOrder = $this->_connectorOrderFactory->create()
                        ->setOrder($salesOrder);
                    $orders[] = $connectorOrder;
                }
                if ($modified) {
                    $this->_orderIdsForSingleSync[] = $order->getOrderId();
                } else {
                    $this->_orderIds[] = $order->getOrderId();
                }
            } catch (\Exception $e) {
                $this->_helper->debug((string) $e, []);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($e->getMessage())
                );
            }
        }

        return $orders;
    }

    /**
     * Create a guest contact.
     *
     * @param $email
     * @param $websiteId
     * @param $storeId
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _createGuestContact($email, $websiteId, $storeId)
    {
        try {
            $client = $this->_helper->getWebsiteApiClient($websiteId);

            //no api credentials or the guest has no been mapped
            if (!$client
                || !$addressBookId
                    = $this->_helper->getGuestAddressBook($websiteId)
            ) {
                return false;
            }

            $contactModel = $this->_contactFactory->create()
                ->loadByCustomerEmail($email, $websiteId);

            //check if contact exists, create if not
            $contactApi = $client->postContacts($email);

            //contact is suppressed cannot add to address book, mark as suppressed.
            if (isset($contactApi->message)
                && $contactApi->message
                == 'Contact is suppressed. ERROR_CONTACT_SUPPRESSED'
            ) {

                //mark new contacts as guest.
                if ($contactModel->isObjectNew()) {
                    $contactModel->setIsGuest(1);
                }
                $contactModel->setSuppressed(1);
                $contactModel->save();

                return false;
            }

            //add guest to address book
            $response = $client->postAddressBookContacts(
                $addressBookId, $contactApi
            );
            //set contact as was found as guest and
            $contactModel->setIsGuest(1)
                ->setStoreId($storeId)
                ->setEmailImported(1);
            //contact id
            if (isset($contactApi->id)) {
                $contactModel->setContactId();
            }
            //mark the contact as surpressed
            if (isset($response->message)
                && $response->message
                == 'Contact is suppressed. ERROR_CONTACT_SUPPRESSED'
            ) {
                $contactModel->setSuppressed(1);
            }
            //save
            $contactModel->save();

            $this->_helper->log(
                '-- guest found : '.$email.' website : '.$websiteId
                .' ,store : '.$storeId
            );
        } catch (\Exception $e) {
            $this->_helper->debug((string) $e, []);
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }

        return true;
    }

    /**
     * Set imported in bulk query.
     *
     * @param            $ids
     * @param bool|false $modified
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _setImported($ids, $modified = false)
    {
        try {
            $coreResource = $this->_resource;
            $write = $coreResource->getConnection('core_write');
            $tableName = $coreResource->getTableName('email_order');
            $ids = implode(', ', $ids);

            if ($modified) {
                $write->update(
                    $tableName, [
                        'modified' => new \Zend_Db_Expr('null'),
                        'updated_at' => gmdate('Y-m-d H:i:s'),
                        "order_id IN ($ids)",
                    ]
                );
            } else {
                $write->update(
                    $tableName, [
                    'email_imported' => 1,
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                ], "order_id IN ($ids)"
                );
            }
        } catch (\Exception $e) {
            $this->_helper->debug((string) $e, []);
        }
    }
}
