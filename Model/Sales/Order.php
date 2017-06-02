<?php

namespace Dotdigitalgroup\Email\Model\Sales;

/**
 * Transactional data for orders to sync.
 */
class Order
{
    /**
     * @var array
     */
    public $accounts = [];
    /**
     * @var string
     */
    public $dateTime;

    /**
     * Global number of orders.
     *
     * @var int
     */
    public $countOrders = 0;

    /**
     * @var array
     */
    public $reviewCollection = [];
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\CampaignFactory
     */
    public $campaignFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory
     */
    public $campaignCollection;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory
     */
    public $orderCollection;
    /**
     * @var \Dotdigitalgroup\Email\Model\RulesFactory
     */
    public $rulesFactory;
    /**
     * @var \Zend_Date
     */
    public $date;
    /**
     * Order constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignCollection
     * @param \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime $datetime
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Zend_Date $date
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignCollection,
        \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime $datetime,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Zend_Date $date
    ) {
        $this->rulesFactory       = $rulesFactory;
        $this->orderCollection    = $orderCollection;
        $this->campaignCollection = $campaignCollection;
        $this->campaignFactory    = $campaignFactory;
        $this->helper             = $helper;
        $this->dateTime           = $datetime;
        $this->storeManager       = $storeManagerInterface;
        $this->date = $date;
    }

    /**
     * Create review campaigns
     */
    public function createReviewCampaigns()
    {
        $this->searchOrdersForReview();

        foreach ($this->reviewCollection as $websiteId => $collection) {
            $this->registerCampaign($collection, $websiteId);
        }
    }

    /**
     * Register review campaign.
     *
     * @param $collection
     * @param $websiteId
     */
    public function registerCampaign($collection, $websiteId)
    {
        //review campaign id
        $campaignId = $this->helper->getCampaign($websiteId);

        if ($campaignId) {
            foreach ($collection as $order) {
                $this->helper->log(
                    '-- Order Review: ' . $order->getIncrementId()
                    . ' Campaign Id: ' . $campaignId
                );

                try {
                    $emailCampaign = $this->campaignFactory->create()
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
                    $emailCampaign->getResource()->saveItem($emailCampaign);
                } catch (\Exception $e) {
                    $this->helper->debug((string)$e, []);
                }
            }
        }
    }

    /**
     * Search for orders to review per website.
     */
    public function searchOrdersForReview()
    {
        $websites = $this->helper->getwebsites(true);

        foreach ($websites as $website) {
            $apiEnabled = $this->helper->isEnabled($website);
            if ($apiEnabled
                && $this->helper->getWebsiteConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED,
                    $website
                )
                && $this->helper->getOrderStatus($website)
                && $this->helper->getDelay($website)
            ) {
                $storeIds = $website->getStoreIds();
                if (empty($storeIds)) {
                    continue;
                }

                $orderStatusFromConfig = $this->helper->getOrderStatus(
                    $website
                );
                $delayInDays = $this->helper->getDelay(
                    $website
                );

                $campaignCollection = $this->campaignCollection->create()
                    ->getCollectionByEvent('Order Review');

                $campaignOrderIds = $campaignCollection->getColumnValues(
                    'order_increment_id'
                );

                $fromTime = $this->date;
                $fromTime->subDay($delayInDays);
                $toTime = clone $fromTime;
                $to = $toTime->toString('YYYY-MM-dd HH:mm:ss');
                $from = $fromTime->subHour(2)
                    ->toString('YYYY-MM-dd HH:mm:ss');

                $created = ['from' => $from, 'to' => $to, 'date' => true];

                $collection = $this->orderCollection->create()
                    ->getSalesCollectionForReviews(
                        $orderStatusFromConfig,
                        $created,
                        $storeIds,
                        $campaignOrderIds
                    );

                //process rules on collection
                $collection = $this->rulesFactory->create()
                    ->process(
                        $collection,
                        \Dotdigitalgroup\Email\Model\Rules::REVIEW,
                        $website->getId()
                    );

                if ($collection->getSize()) {
                    $this->reviewCollection[$website->getId()] = $collection;
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
    )
    {
        $storeIds = $this->storeManager->getWebsite(
            $customer->getWebsiteId()
        )->getStoreIds();

        return $this->orderCollection->create()
            ->getCustomerLastOrderId($customer, $storeIds);
    }

    /**
     * Get customer last quote id.
     *
     * @param \Magento\Customer\Model\Customer $customer
     *
     * @return bool|mixed
     */
    public function getCustomerLastQuoteId(\Magento\Customer\Model\Customer $customer
    )
    {
        $storeIds = $this->storeManager->getWebsite(
            $customer->getWebsiteId()
        )->getStoreIds();

        return $this->orderCollection->create()
            ->getCustomerLastQuoteId($customer, $storeIds);
    }
}
