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
	/**
	 * Global number of orders
	 * @var int
	 */
	private $_countOrders = 0;

    private $_reviewCollection = array();

	protected $_helper;
	protected $_objectManager;
	protected $_resource;
	protected $_scopeConfig;
	protected $_reviewHelper;
	protected $_storeManager;
	public $dateTime;
	protected $_campaignFactory;
	protected $_campaignCollection;
	protected $_orderCollection;
	protected $_rulesFactory;
	protected $_quoteCollection;

	public function __construct(
		\Magento\Quote\Model\Resource\Quote\CollectionFactory $quoteCollection,
		\Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
		\Magento\Sales\Model\Resource\Order\CollectionFactory $orderCollection,
		\Dotdigitalgroup\Email\Model\Resource\Campaign\CollectionFactory $campaignCollection,
		\Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory,
		\Magento\Framework\App\Resource $resource,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\Stdlib\Datetime $datetime,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Dotdigitalgroup\Email\Helper\Review $reviewHelper,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\ObjectManagerInterface $objectManager
	)
	{
		$this->_quoteCollection = $quoteCollection;
		$this->_rulesFactory = $rulesFactory;
		$this->_orderCollection = $orderCollection;
		$this->_campaignCollection = $campaignCollection;
		$this->_campaignFactory = $campaignFactory;
		$this->_helper = $helper;
		$this->_reviewHelper  = $reviewHelper;
		$this->_resource = $resource;
		$this->dateTime = $datetime;
		$this->_storeManager = $storeManagerInterface;
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
     */
    private function registerCampaign($collection, $websiteId)
    {
	    //review campaign id
        $campaignId = $this->_reviewHelper->getCampaign($websiteId);

        if($campaignId) {
            foreach ($collection as $order) {
                $this->_helper->log('-- Order Review: ' . $order->getIncrementId() . ' Campaign Id: ' . $campaignId);

                try {
                    $emailCampaign = $this->_campaignFactory->create()
                        ->setEmail($order->getCustomerEmail())
                        ->setStoreId($order->getStoreId())
                        ->setCampaignId($campaignId)
                        ->setEventName('Order Review')
                        ->setCreatedAt($this->dateTime->formatDate(true))
                        ->setOrderIncrementId($order->getIncrementId())
                        ->setQuoteId($order->getQuoteId());

                    if($order->getCustomerId())
                        $emailCampaign->setCustomerId($order->getCustomerId());

                    $emailCampaign->save();
                } catch (\Exception $e) {
	                $this->_helper->debug((string)$e, array());
                }
            }
        }
    }

    /**
     * search for orders to review per website
     */
    private function searchOrdersForReview()
    {
		$websites = $this->_helper->getwebsites(true);

        foreach ($websites as $website){

	        $apiEnabled = $this->_helper->isEnabled($website);
            if($apiEnabled &&
               $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED, $website) &&
               $this->_reviewHelper->getOrderStatus($website) &&
               $this->_reviewHelper->getDelay($website))
            {

                $storeIds = $website->getStoreIds();
                if(empty($storeIds))
                    continue;

                $orderStatusFromConfig = $this->_reviewHelper->getOrderStatus($website);
                $delayInDays = $this->_reviewHelper->getDelay($website);

                $campaignCollection = $this->_campaignCollection->create()
                    ->addFieldToFilter('event_name', 'Order Review')
                    ->load();

                $campaignOrderIds = $campaignCollection->getColumnValues('order_increment_id');


	            $fromTime = new \Zend_Date();
	            $fromTime->subDay($delayInDays);
	            $toTime = clone $fromTime;
                $to = $toTime->toString('YYYY-MM-dd HH:mm:ss');
                $from = $fromTime->subHour(2)
                    ->toString('YYYY-MM-dd HH:mm:ss');

                $created = array( 'from' => $from, 'to' => $to, 'date' => true);

                $collection = $this->_orderCollection->create()
                    ->addFieldToFilter('main_table.status', $orderStatusFromConfig)
                    ->addFieldToFilter('main_table.created_at', $created)
                    ->addFieldToFilter('main_table.store_id', array('in' => $storeIds));

                if(!empty($campaignOrderIds))
                    $collection->addFieldToFilter('main_table.increment_id', array('nin' => $campaignOrderIds));

                //process rules on collection
                $collection = $this->_rulesFactory->create()
	                ->process(
                    $collection, \Dotdigitalgroup\Email\Model\Rules::REVIEW, $website->getId()
                );

                if($collection->getSize())
                    $this->_reviewCollection[$website->getId()] = $collection;
            }
        }
    }

    /**
     * get customer last order id
     *
     */
    public function getCustomerLastOrderId(\Magento\Customer\Model\Customer $customer)
    {
        $storeIds = $this->_storeManager->getWebsite($customer->getWebsiteId())->getStoreIds();
        $collection = $this->_orderCollection->create()
            ->addFieldToFilter('customer_id', $customer->getId())
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
     */
    public function getCustomerLastQuoteId(\Magento\Customer\Model\Customer $customer)
    {
        $storeIds = $this->_storeManager->getWebsite($customer->getWebsiteId())->getStoreIds();
        $collection = $this->_quoteCollection->create()
            ->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('store_id', array('in' => $storeIds))
            ->setPageSize(1)
            ->setOrder('entity_id');

        if ($collection->count())
            return $collection->getFirstItem();
        else
            return false;
    }

}