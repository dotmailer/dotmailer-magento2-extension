<?php

namespace Dotdigitalgroup\Email\Model\Sales;

use Magento\Store\Model\Website;

/**
 * Transactional data for orders to sync.
 */
class Order
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign
     */
    private $campaignResource;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * @var array
     */
    private $reviewCollection = [];

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\CampaignFactory
     */
    private $campaignFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory
     */
    private $campaignCollection;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollection;

    /**
     * @var \Dotdigitalgroup\Email\Model\RulesFactory
     */
    private $rulesFactory;

    /**
     * Order constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignCollection
     * @param \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign $campaignResource
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime $datetime
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignCollection,
        \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign $campaignResource,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime $datetime,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->campaignResource = $campaignResource;
        $this->rulesFactory       = $rulesFactory;
        $this->orderCollection    = $orderCollection;
        $this->campaignCollection = $campaignCollection;
        $this->campaignFactory    = $campaignFactory;
        $this->helper             = $helper;
        $this->dateTime           = $datetime;
        $this->storeManager       = $storeManagerInterface;
    }

    /**
     * Create review campaigns
     *
     * @return void
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
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $collection
     * @param int $websiteId
     *
     * @return void
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
                    $this->campaignResource->saveItem($emailCampaign);
                } catch (\Exception $e) {
                    $this->helper->debug((string)$e, []);
                }
            }
        }
    }

    /**
     * Search for orders to review per website.
     *
     * @return void
     * @throws \Exception
     */
    public function searchOrdersForReview()
    {
        $websites = $this->helper->getwebsites(true);

        /** @var Website $website */
        foreach ($websites as $website) {
            $apiEnabled = $this->helper->isEnabled($website);
            if ($apiEnabled
                && $this->helper->getWebsiteConfig(
                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_REVIEWS_ENABLED,
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
                $fromTime = new \DateTime('now', new \DateTimezone('UTC'));
                $interval = new \DateInterval(sprintf('P%sD', $delayInDays));
                $fromTime->sub($interval);
                $toTime = clone $fromTime;
                $fromTime->sub(new \DateInterval('PT2H'));
                $fromDate = $fromTime->format('Y-m-d H:i:s');
                $toDate = $toTime->format('Y-m-d H:i:s');

                $created = ['from' => $fromDate, 'to' => $toDate, 'date' => true];

                $collection = $this->orderCollection->create()
                    ->getSalesCollectionForReviews(
                        $orderStatusFromConfig,
                        $created,
                        $website,
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
}
