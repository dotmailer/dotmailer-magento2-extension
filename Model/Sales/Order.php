<?php

namespace Dotdigitalgroup\Email\Model\Sales;

class Order
{
	/**
	 * @var array
	 */
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

    private $_reviewCollection = array();
    private $_orderIds;
    private $_orderIdsForSingleSync;

	protected $_helper;
	protected $_objectManager;
	protected $_resource;
	protected $_scopeConfig;

	public function __construct(
		\Magento\Framework\App\Resource $resource,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\ObjectManagerInterface $objectManager
	)
	{
		$this->_helper = $helper;
		$this->_resource = $resource;
		$this->_scopeConfig = $scopeConfig;
		$this->_objectManager = $objectManager;
	}

    /**
     * initial sync the transactional data
     * @return array
     */
    public function sync()
    {
        $response = array('success' => true, 'message' => '');
	    $client = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Apiconnector\Client');

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
                //$check = Mage::getModel('ddg_automation/importer')
	            $check = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')
	                ->registerQueue(
                    \Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_ORDERS,
                    $orders,
		                \Dotdigitalgroup\Email\Model\Proccessor::MODE_BULK,
                    $website[0]
                );
                //if no error then set imported
                if ($check) {
                    $this->_setImported($orderIds);
                }
                $this->_helper->log('----------end order sync----------');
            }

            if ($numOrdersForSingleSync) {
                $error = false;
                foreach ($ordersForSingleSync as $order) {
                    $this->_helper->log('--------- register Order sync in single with importer ---------- : ' . $order->id);
                    //register in queue with importer
	                $check = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')
		                ->registerQueue(
			                \Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_ORDERS,
                            $order,
			                \Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE,
                            $website[0]
                    );
                    if (!$check) {
                        $error = true;
                    }
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
            if ($apiEnabled &&
                $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED, $website) &&
                !empty($storeIds)) {

                $this->_apiUsername = $this->_helper->getApiUsername($website);
                $this->_apiPassword = $this->_helper->getApiPassword($website);

                // limit for orders included to sync
                $limit = $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
                if (!isset($this->accounts[$this->_apiUsername])) {
	                $account = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Connector\Account')
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
     * get all order to import.
     * @param $website
     * @param int $limit
     * @param $modified
     *
     * @return array
     */
    public function getConnectorOrders($website, $limit = 100, $modified = false)
    {
        $orders = $customers = array();
        $storeIds = $website->getStoreIds();
        $orderModel   = Mage::getModel('ddg_automation/order');
        if(empty($storeIds))
            return array();

        $helper = Mage::helper('ddg');
        $orderStatuses = $helper->getConfigSelectedStatus($website);

        if ($orderStatuses) {
            if ($modified)
                $orderCollection = $orderModel->getOrdersToImport($storeIds, $limit, $orderStatuses, true);
            else
                $orderCollection = $orderModel->getOrdersToImport($storeIds, $limit, $orderStatuses);
        }
        else
            return array();

        foreach ($orderCollection as $order) {
            try {
                $salesOrder = Mage::getModel('sales/order')->load($order->getOrderId());
                $storeId = $order->getStoreId();
                $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
                /**
                 * Add guest to contacts table.
                 */
                if ($salesOrder->getCustomerIsGuest()) {
                    $this->_createGuestContact($salesOrder->getCustomerEmail(), $websiteId, $storeId);
                }
                if ($salesOrder->getId()) {
                    $connectorOrder = Mage::getModel('ddg_automation/connector_order', $salesOrder);
                    $orders[] = $connectorOrder;
                }
                if ($modified)
                    $this->_orderIdsForSingleSync[] = $order->getOrderId();
                else
                    $this->_orderIds[] = $order->getOrderId();
            }catch(Exception $e){
                Mage::logException($e);
            }
        }
        return $orders;
    }

	/**
	 * Create a guest contact.
	 * @param $email
	 * @param $websiteId
	 * @param $storeId
	 *
	 * @return bool
	 */
	private function _createGuestContact($email, $websiteId, $storeId){
        try{
            $client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);

	        //no api credentials or the guest has no been mapped
	        if (! $client || ! $addressBookId = Mage::helper('ddg')->getGuestAddressBook($websiteId))
		        return false;

	        $contactModel = Mage::getModel('ddg_automation/contact')->loadByCustomerEmail($email, $websiteId);

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

            Mage::helper('ddg')->log('-- guest found : '  . $email . ' website : ' . $websiteId . ' ,store : ' . $storeId);
        }catch(Exception $e){
	        Mage::logException($e);
        }

        return true;
    }


    /**
     * create review campaigns
     *
     * @return bool
     */
    public function createReviewCampaigns()
    {
        $this->searchOrdersForReview();

        foreach($this->_reviewCollection as $websiteId => $collection){
            $this->registerCampaign($collection, $websiteId);
        }
    }

    /**
     * register review campaign
     *
     * @param $collection
     * @param $websiteId
     *
     * @throws Exception
     */
    private function registerCampaign($collection, $websiteId)
    {
        $helper = Mage::helper('ddg/review');
        $campaignId = $helper->getCampaign($websiteId);

        if($campaignId) {
            foreach ($collection as $order) {
                Mage::helper('ddg')->log('-- Order Review: ' . $order->getIncrementId() . ' Campaign Id: ' . $campaignId);

                try {
                    $emailCampaign = Mage::getModel('ddg_automation/campaign');
                    $emailCampaign
                        ->setEmail($order->getCustomerEmail())
                        ->setStoreId($order->getStoreId())
                        ->setCampaignId($campaignId)
                        ->setEventName('Order Review')
                        ->setCreatedAt(Mage::getSingleton('core/date')->gmtDate())
                        ->setOrderIncrementId($order->getIncrementId())
                        ->setQuoteId($order->getQuoteId());

                    if($order->getCustomerId())
                        $emailCampaign->setCustomerId($order->getCustomerId());

                    $emailCampaign->save();
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }
    }

    /**
     * search for orders to review per website
     */
    private function searchOrdersForReview()
    {
        $helper = Mage::helper('ddg/review');

        foreach (Mage::app()->getWebsites(true) as $website){
            $apiEnabled = Mage::helper('ddg')->getWebsiteConfig(Dotdigitalgroup_Email_Helper_Config::XML_PATH_CONNECTOR_API_ENABLED, $website);
            if($apiEnabled && $helper->isEnabled($website) &&
                $helper->getOrderStatus($website) &&
                    $helper->getDelay($website)){

                $storeIds = $website->getStoreIds();
                if(empty($storeIds))
                    continue;

                $orderStatusFromConfig = $helper->getOrderStatus($website);
                $delayInDays = $helper->getDelay($website);

                $campaignCollection = Mage::getModel('ddg_automation/campaign')->getCollection();
                $campaignCollection
                    ->addFieldToFilter('event_name', 'Order Review')
                    ->load();

                $campaignOrderIds = $campaignCollection->getColumnValues('order_increment_id');

                $to = Mage::app()->getLocale()->date()
                    ->subDay($delayInDays);
                $from = clone $to;
                $to = $to->toString('YYYY-MM-dd HH:mm:ss');
                $from = $from->subHour(2)
                    ->toString('YYYY-MM-dd HH:mm:ss');

                $created = array( 'from' => $from, 'to' => $to, 'date' => true);

                $collection = Mage::getModel('sales/order')->getCollection();
                    $collection->addFieldToFilter('main_table.status', $orderStatusFromConfig)
                    ->addFieldToFilter('main_table.created_at', $created)
                    ->addFieldToFilter('main_table.store_id', array('in' => $storeIds));

                if(!empty($campaignOrderIds))
                    $collection->addFieldToFilter('main_table.increment_id', array('nin' => $campaignOrderIds));

                //process rules on collection
                $ruleModel = Mage::getModel('ddg_automation/rules');
                $collection = $ruleModel->process(
                    $collection, Dotdigitalgroup_Email_Model_Rules::REVIEW, $website->getId()
                );

                if($collection->getSize())
                    $this->_reviewCollection[$website->getId()] = $collection;
            }
        }
    }

    /**
     * get customer last order id
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return bool|Varien_Object
     */
    public function getCustomerLastOrderId(Mage_Customer_Model_Customer $customer)
    {
        $storeIds = Mage::app()->getWebsite($customer->getWebsiteId())->getStoreIds();
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('store_id', array('in' => $storeIds))
            ->setPageSize(1)
            ->setOrder('entity_id');

        if ($collection->count())
            return $collection->getFirstItem();
        else
            return false;
    }

    /**
     * get customer last quote id
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return bool|Varien_Object
     */
    public function getCustomerLastQuoteId(Mage_Customer_Model_Customer $customer)
    {
        $storeIds = Mage::app()->getWebsite($customer->getWebsiteId())->getStoreIds();
        $collection = Mage::getModel('sales/quote')->getCollection();
        $collection->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('store_id', array('in' => $storeIds))
            ->setPageSize(1)
            ->setOrder('entity_id');

        if ($collection->count())
            return $collection->getFirstItem();
        else
            return false;
    }

    /**
     * set imported in bulk query
     *
     * @param $ids
     * @param $modified
     */
    private function _setImported($ids, $modified = false)
    {
        try{
            $coreResource = Mage::getSingleton('core/resource');
            $write = $coreResource->getConnection('core_write');
            $tableName = $coreResource->getTableName('email_order');
            $ids = implode(', ', $ids);
            $now = Mage::getSingleton('core/date')->gmtDate();

            if ($modified)
                $write->update($tableName, array('modified' => new Zend_Db_Expr('null'), 'updated_at' => $now), "order_id IN ($ids)");
            else
                $write->update($tableName, array('email_imported' => 1, 'updated_at' => $now), "order_id IN ($ids)");
        }catch (Exception $e){
            Mage::logException($e);
        }
    }
}