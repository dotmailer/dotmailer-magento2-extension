<?php

namespace Dotdigitalgroup\Email\Model\Sales;

class Order
{
    /**
     * @var array
     */
    protected $accounts = [];
    /**
     * @var string
     */
    public $dateTime;

    /**
     * Global number of orders.
     *
     * @var int
     */
    protected $_countOrders = 0;

    /**
     * @var array
     */
    protected $_reviewCollection = [];
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\CampaignFactory
     */
    protected $_campaignFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Resource\Campaign\CollectionFactory
     */
    protected $_campaignCollection;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollection;
    /**
     * @var \Dotdigitalgroup\Email\Model\RulesFactory
     */
    protected $_rulesFactory;
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $_quoteCollection;

    /**
     * Order constructor.
     *
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory       $quoteCollection
     * @param \Dotdigitalgroup\Email\Model\RulesFactory                        $rulesFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory       $orderCollection
     * @param \Dotdigitalgroup\Email\Model\Resource\Campaign\CollectionFactory $campaignCollection
     * @param \Dotdigitalgroup\Email\Model\CampaignFactory                     $campaignFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                               $helper
     * @param \Magento\Framework\Stdlib\Datetime                               $datetime
     * @param \Magento\Store\Model\StoreManagerInterface                       $storeManagerInterface
     */
    public function __construct(
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollection,
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Dotdigitalgroup\Email\Model\Resource\Campaign\CollectionFactory $campaignCollection,
        \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Stdlib\Datetime $datetime,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_quoteCollection = $quoteCollection;
        $this->_rulesFactory = $rulesFactory;
        $this->_orderCollection = $orderCollection;
        $this->_campaignCollection = $campaignCollection;
        $this->_campaignFactory = $campaignFactory;
        $this->_helper = $helper;
        $this->dateTime = $datetime;
        $this->_storeManager = $storeManagerInterface;
    }

    /**
     * Create review campaigns.
     *
     * @return bool
     */
    public function createReviewCampaigns()
    {
        $this->searchOrdersForReview();

        foreach ($this->_reviewCollection as $websiteId => $collection) {
            $this->registerCampaign($collection, $websiteId);
        }
    }

    /**
     * Register review campaign.
     *
     * @param $collection
     * @param $websiteId
     */
    protected function registerCampaign($collection, $websiteId)
    {
        //review campaign id
        $campaignId = $this->_helper->getCampaign($websiteId);

        if ($campaignId) {
            foreach ($collection as $order) {
                $this->_helper->log(
                    '-- Order Review: '.$order->getIncrementId()
                    .' Campaign Id: '.$campaignId
                );

                try {
                    $emailCampaign = $this->_campaignFactory->create()
                        ->setEmail($order->getCustomerEmail())
                        ->setStoreId($order->getStoreId())
                        ->setCampaignId($campaignId)
                        ->setEventName('Order Review')
                        ->setCreatedAt($this->dateTime->formatDate(true))
                        ->setOrderIncrementId($order->getIncrementId())
                        ->setQuoteId($order->getQuoteId());

                    if ($order->getCustomerId()) {
                        $emailCampaign->setCustomerId($order->getCustomerId());
                    }

                    $emailCampaign->save();
                } catch (\Exception $e) {
                    $this->_helper->debug((string) $e, []);
                }
            }
        }
    }

    /**
     * Search for orders to review per website.
     */
    protected function searchOrdersForReview()
    {
        $websites = $this->_helper->getwebsites(true);

        foreach ($websites as $website) {
            $apiEnabled = $this->_helper->isEnabled($website);
            if ($apiEnabled
                && $this->_helper->getWebsiteConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
                    $website
                )
                && $this->_helper->getOrderStatus($website)
                && $this->_helper->getDelay($website)
            ) {
                $storeIds = $website->getStoreIds();
                if (empty($storeIds)) {
                    continue;
                }

                $orderStatusFromConfig = $this->_helper->getOrderStatus(
                    $website
                );
                $delayInDays = $this->_helper->getDelay(
                    $website
                );

                $campaignCollection = $this->_campaignCollection->create()
                    ->addFieldToFilter('event_name', 'Order Review')
                    ->load();

                $campaignOrderIds = $campaignCollection->getColumnValues(
                    'order_increment_id'
                );

                $fromTime = new \Zend_Date();
                $fromTime->subDay($delayInDays);
                $toTime = clone $fromTime;
                $to = $toTime->toString('YYYY-MM-dd HH:mm:ss');
                $from = $fromTime->subHour(2)
                    ->toString('YYYY-MM-dd HH:mm:ss');

                $created = ['from' => $from, 'to' => $to, 'date' => true];

                $collection = $this->_orderCollection->create()
                    ->addFieldToFilter(
                        'main_table.status', $orderStatusFromConfig
                    )
                    ->addFieldToFilter('main_table.created_at', $created)
                    ->addFieldToFilter(
                        'main_table.store_id', ['in' => $storeIds]
                    );

                if (!empty($campaignOrderIds)) {
                    $collection->addFieldToFilter(
                        'main_table.increment_id',
                        ['nin' => $campaignOrderIds]
                    );
                }

                //process rules on collection
                $collection = $this->_rulesFactory->create()
                    ->process(
                        $collection, \Dotdigitalgroup\Email\Model\Rules::REVIEW,
                        $website->getId()
                    );

                if ($collection->getSize()) {
                    $this->_reviewCollection[$website->getId()] = $collection;
                }
            }
        }
    }

    /**
     * Get customer last order id.
     * 
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return bool|mixed
     */
    public function getCustomerLastOrderId(\Magento\Customer\Model\Customer $customer
    ) {
        $storeIds = $this->_storeManager->getWebsite(
            $customer->getWebsiteId()
        )->getStoreIds();
        $collection = $this->_orderCollection->create()
            ->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->setPageSize(1)
            ->setOrder('entity_id');

        if ($collection->count()) {
            return $collection->getFirstItem();
        } else {
            return false;
        }
    }

    /**
     * Get customer last quote id.
     * 
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return bool|mixed
     */
    public function getCustomerLastQuoteId(\Magento\Customer\Model\Customer $customer
    ) {
        $storeIds = $this->_storeManager->getWebsite(
            $customer->getWebsiteId()
        )->getStoreIds();
        $collection = $this->_quoteCollection->create()
            ->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->setPageSize(1)
            ->setOrder('entity_id');

        if ($collection->count()) {
            return $collection->getFirstItem();
        } else {
            return false;
        }
    }
}
