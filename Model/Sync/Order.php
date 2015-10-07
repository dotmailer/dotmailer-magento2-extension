<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Order
{
	protected $accounts = array();
	/**
	 * @var string
	 */
	private $_apiUsername;
	/**
	 * @var string
	 */
	private $_apiPassword;

	/**
	 * Global number of orders
	 * @var int
	 */
	private $_countOrders = 0;

	private $_orderIds;
	private $_orderIdsForSingleSync;

	protected $_helper;
	protected $_storeManager;
	protected $_resource;
	protected $_scopeConfig;
	protected $_contactFactory;
	protected $_orderFactory;
	protected $_salesOrderFactory;
	protected $_connectorOrderFactory;
	protected $_accountFactory;
	protected $_proccessorFactory;

	public function __construct(
		\Dotdigitalgroup\Email\Model\ProccessorFactory $proccessorFactory,
		\Dotdigitalgroup\Email\Model\Connector\AccountFactory $accountFactory,
		\Magento\Sales\Model\OrderFactory $salesOrderFactory,
		\Dotdigitalgroup\Email\Model\Connector\OrderFactory $connectorOrderFactory,
		\Dotdigitalgroup\Email\Model\OrderFactory $orderFactory,
		\Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
		\Magento\Framework\App\Resource $resource,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\ObjectManagerInterface $objectManager
	)
	{
		$this->_proccessorFactory = $proccessorFactory;
		$this->_connectorOrderFactory = $connectorOrderFactory;
		$this->_accountFactory = $accountFactory;
		$this->_salesOrderFactory = $salesOrderFactory;
		$this->_orderFactory = $orderFactory;
		$this->_contactFactory = $contactFactory;
		$this->_helper = $helper;
		$this->_storeManager = $storeManagerInterface;
		$this->_resource = $resource;
		$this->_scopeConfig = $scopeConfig;
	}

	/**
	 * initial sync the transactional data
	 * @return array
	 */
	public function sync()
	{
		$response = array('success' => true, 'message' => 'Done.');

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
				$this->_helper->log('--------- register Order sync with importer ---------- : ' . count($orders));
				//register in queue with importer
				//$this->_helper->debug('orders', $orders);
				$this->_helper->error('orders', $orders);
				try {
					$this->_proccessorFactory->create()
                         ->registerQueue(
	                         \Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_ORDERS,
	                         $orders,
	                         \Dotdigitalgroup\Email\Model\Proccessor::MODE_BULK,
	                         $website[0]
                         );
				}catch (\Exception $e) {
					$this->_helper->debug((string)$e, array() );
					throw new \Magento\Framework\Exception\LocalizedException( __( $e->getMessage() ) );
				}

				$this->_setImported($orderIds);

				$this->_helper->log('----------end order sync----------');
			}

			if ($numOrdersForSingleSync) {
				$error = false;
				foreach ($ordersForSingleSync as $order) {
					$this->_helper->log('--------- register Order sync in single with importer ---------- : ' . $order->id);
					//register in queue with importer
					$this->_proccessorFactory->create()
	                      ->registerQueue(
	                          \Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_ORDERS,
	                          $order,
	                          \Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE,
	                          $website[0]
	                      );
					$this->_helper->log('----------end order sync in single----------');
				}
				//if no error then set imported
				if (!$error) {
					$this->_setImported($orderIdsForSingleSync, true);
				}
			}
			unset($this->accounts[$account->getApiUsername()]);
		}

		if ($this->_countOrders)
			$response['message'] = 'Number of updated orders : ' . $this->_countOrders;
		return $response;
	}

	/**
	 * Search the configuration data per website
	 */
	private function _searchAccounts()
	{
		$this->_orderIds = array();
		$websites = $this->_helper->getWebsites(true);
		foreach ($websites as $website) {
			$apiEnabled = $this->_helper->isEnabled($website);
			$storeIds = $website->getStoreIds();
			// api and order sync should be enabled, skip website with no store ids
			if ($apiEnabled &&
			    $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED, $website) &&
			    !empty($storeIds)) {

				$this->_apiUsername = $this->_helper->getApiUsername($website);
				$this->_apiPassword = $this->_helper->getApiPassword($website);
				// limit for orders included to sync
				$limit = $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
				if (!isset($this->accounts[$this->_apiUsername])) {
					$account = $this->_accountFactory->create()
                        ->setApiUsername($this->_apiUsername)
                        ->setApiPassword($this->_apiPassword);
					$this->accounts[$this->_apiUsername] = $account;
				}
				$this->accounts[$this->_apiUsername]->setOrders($this->getConnectorOrders($website, $limit));
				$this->accounts[$this->_apiUsername]->setOrderIds($this->_orderIds);
				$this->accounts[$this->_apiUsername]->setWebsites($website->getId());
				$this->accounts[$this->_apiUsername]->setOrdersForSingleSync($this->getConnectorOrders($website, $limit, true));
				$this->accounts[$this->_apiUsername]->setOrderIdsForSingleSync($this->_orderIdsForSingleSync);
			}
		}
	}


	/**
	 * get all orders to import.
	 * @param $website
	 * @param int $limit
	 * @param bool|false $modified
	 *
	 * @return array
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function getConnectorOrders($website, $limit = 100, $modified = false)
	{
		$orders = $customers = array();
		$storeIds = $website->getStoreIds();
		$orderModel = $this->_orderFactory->create();
		if(empty($storeIds))
			return array();

		$orderStatuses = $this->_helper->getConfigSelectedStatus($website);

		//any statuses found
		if ($orderStatuses) {
			if ($modified)
				$orderCollection = $orderModel->getOrdersToImport($storeIds, $limit, $orderStatuses, true);
			else
				$orderCollection = $orderModel->getOrdersToImport($storeIds, $limit, $orderStatuses);
		} else
			return array();

		foreach ($orderCollection as $order) {

			try {
				$salesOrder = $this->_salesOrderFactory->create()->load($order->getOrderId());
				$storeId = $order->getStoreId();
				$websiteId = $this->_storeManager->getStore($storeId)->getWebsiteId();
				/**
				 * Add guest to contacts table.
				 */
				if ($salesOrder->getCustomerIsGuest()) {
					$this->_createGuestContact($salesOrder->getCustomerEmail(), $websiteId, $storeId);
				}
				if ($salesOrder->getId()) {
					$connectorOrder = $this->_connectorOrderFactory->create()
						->setOrder($salesOrder);
					$orders[] = $connectorOrder;
				}
				if ($modified)
					$this->_orderIdsForSingleSync[] = $order->getOrderId();
				else
					$this->_orderIds[] = $order->getOrderId();
			}catch(\Exception $e){

				$this->_helper->debug((string)$e, array() );
				throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));

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
	 * @return bool|void
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	private function _createGuestContact($email, $websiteId, $storeId){
		try{
			$client = $this->_helper->getWebsiteApiClient($websiteId);

			//no api credentials or the guest has no been mapped
			if (! $client || ! $addressBookId = $this->_helper->getGuestAddressBook($websiteId))
				return false;

			$contactModel = $this->_contactFactory->create()
				->loadByCustomerEmail($email, $websiteId);

			//check if contact exists, create if not
			$contactApi = $client->postContacts($email);

			//contact is suppressed cannot add to address book, mark as suppressed.
			if (isset($contactApi->message) && $contactApi->message == 'Contact is suppressed. ERROR_CONTACT_SUPPRESSED'){

					//mark new contacts as guest.
				if ($contactModel->isObjectNew())
					$contactModel->setIsGuest(1);
				$contactModel->setSuppressed(1);
				$contactModel->save();
				return;
			}

			//add guest to address book
			$response = $client->postAddressBookContacts($addressBookId, $contactApi);
			//set contact as was found as guest and
			$contactModel->setIsGuest(1)
			             ->setStoreId($storeId)
			             ->setEmailImported(1);
			//contact id
			if (isset($contactApi->id))
				$contactModel->setContactId();
			//mark the contact as surpressed
			if (isset($response->message) && $response->message == 'Contact is suppressed. ERROR_CONTACT_SUPPRESSED')
				$contactModel->setSuppressed(1);
			//save
			$contactModel->save();

			$this->_helper->log('-- guest found : '  . $email . ' website : ' . $websiteId . ' ,store : ' . $storeId);
		}catch(\Exception $e){
			$this->_helper->debug((string)$e, array() );
			throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
		}

		return true;
	}

	/**
	 * set imported in bulk query
	 * //@todo dry as it's used in many places with same logic
	 * @param $ids
	 * @param bool|false $modified
	 *
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	private function _setImported($ids, $modified = false)
	{
		try{
			$coreResource = $this->_resource;
			$write = $coreResource->getConnection('core_write');
			$tableName = $coreResource->getTableName('email_order');
			$ids = implode(', ', $ids);

			if ($modified)
				$write->update($tableName, array('modified' => new \Zend_Db_Expr('null'), 'updated_at' =>
					gmdate('Y-m-d H:i:s'), "order_id IN ($ids)"));
			else
				$write->update($tableName, array('email_imported' => 1, 'updated_at' => gmdate('Y-m-d H:i:s')), "order_id IN ($ids)");
		}catch (\Exception $e){
			$this->_helper->debug((string)$e, array() );
		}
	}
}