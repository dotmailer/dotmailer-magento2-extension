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