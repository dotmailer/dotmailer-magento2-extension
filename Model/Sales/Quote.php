<?php

namespace Dotdigitalgroup\Email\Model\Sales;

use Dotdigitalgroup\Email\Model\ResourceModel\Campaign;

/**
 * Customer and guest Abandoned Carts.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Quote
{
    //customer
    const XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_1 = 'abandoned_carts/customers/enabled_1';
    const XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_2 = 'abandoned_carts/customers/enabled_2';
    const XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_3 = 'abandoned_carts/customers/enabled_3';
    const XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_1 = 'abandoned_carts/customers/send_after_1';
    const XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_2 = 'abandoned_carts/customers/send_after_2';
    const XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_3 = 'abandoned_carts/customers/send_after_3';
    const XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_1 = 'abandoned_carts/customers/campaign_1';
    const XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_2 = 'abandoned_carts/customers/campaign_2';
    const XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_3 = 'abandoned_carts/customers/campaign_3';

    //guest
    const XML_PATH_LOSTBASKET_GUEST_ENABLED_1 = 'abandoned_carts/guests/enabled_1';
    const XML_PATH_LOSTBASKET_GUEST_ENABLED_2 = 'abandoned_carts/guests/enabled_2';
    const XML_PATH_LOSTBASKET_GUEST_ENABLED_3 = 'abandoned_carts/guests/enabled_3';
    const XML_PATH_LOSTBASKET_GUEST_INTERVAL_1 = 'abandoned_carts/guests/send_after_1';
    const XML_PATH_LOSTBASKET_GUEST_INTERVAL_2 = 'abandoned_carts/guests/send_after_2';
    const XML_PATH_LOSTBASKET_GUEST_INTERVAL_3 = 'abandoned_carts/guests/send_after_3';
    const XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_1 = 'abandoned_carts/guests/campaign_1';
    const XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_2 = 'abandoned_carts/guests/campaign_2';
    const XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_3 = 'abandoned_carts/guests/campaign_3';

    /**
     * @var object
     */
    public $quoteCollection;

    /**
     * @var Campaign
     */
    private $campaignResource;

    /**
     * Number of lost baskets available.
     *
     * @var array
     */
    private $lostBasketCustomers = [1, 2, 3];

    /**
     * Number of guest lost baskets available.
     *
     * @var array
     */
    private $lostBasketGuests = [1, 2, 3];

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollection;

    /**
     * @var \Dotdigitalgroup\Email\Model\CampaignFactory
     */
    private $campaignFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory
     */
    private $campaignCollection;

    /**
     * @var \Dotdigitalgroup\Email\Model\RulesFactory
     */
    private $rulesFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timeZone;

    /**
     * Total number of customers found.
     * @var int
     */
    public $totalCustomers = 0;

    /**
     * Total number of guest found.
     * @var int
     */
    public $totalGuests = 0;

    /**
     * Quote constructor.
     * @param \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory
     * @param Campaign\CollectionFactory $campaignCollection
     * @param Campaign $campaignResource
     * @param \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignCollection,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign $campaignResource,
        \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->rulesFactory = $rulesFactory;
        $this->helper = $helper;
        $this->campaignCollection = $campaignCollection;
        $this->campaignResource = $campaignResource;
        $this->campaignFactory = $campaignFactory;
        $this->storeManager = $storeManager;
        $this->orderCollection = $collectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->timeZone = $timezone;
    }

    /**
     * Proccess abandoned carts.
     *
     * @param string $mode
     *
     * @return $this
     */
    public function proccessAbandonedCarts($mode = 'all')
    {
        /*
         * Save lost baskets to be send in Send table.
         */
        $stores = $this->helper->getStores();

        foreach ($stores as $store) {
            $storeId = $store->getId();

            if ($mode == 'all' || $mode == 'customers') {
                $this->searchForCustomerAbandonedCarts($storeId);
            }
            if ($mode == 'all' || $mode == 'guests') {
                $this->searchForGuestAbandonedCarts($storeId);
            }
        }

        return $this;
    }

    /**
     * @param int $num
     * @param int $storeId
     *
     * @return bool
     */
    public function isLostBasketCustomerEnabled($num, $storeId)
    {
        return $this->scopeConfig->isSetFlag(
            constant('self::XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_' . $num),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int $num
     * @param int $storeId
     *
     * @return mixed
     */
    public function getLostBasketCustomerInterval($num, $storeId)
    {
        return $this->scopeConfig->getValue(
            constant('self::XML_PATH_LOSTBASKET_CUSTOMER_INTERVAL_' . $num),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param mixed $from
     * @param mixed $to
     * @param bool|false $guest
     * @param int $storeId
     *
     * @return $this
     */
    public function getStoreQuotes($from = null, $to = null, $guest = false, $storeId = 0)
    {
        $updated = [
            'from' => $from,
            'to' => $to,
            'date' => true,
        ];

        $salesCollection = $this->orderCollection->create()
            ->getStoreQuotes($storeId, $updated, $guest);

        //process rules on collection
        $ruleModel = $this->rulesFactory->create();
        $websiteId = $this->storeManager->getStore($storeId)
            ->getWebsiteId();
        $salesCollection = $ruleModel->process(
            $salesCollection,
            \Dotdigitalgroup\Email\Model\Rules::ABANDONED,
            $websiteId
        );

        $this->quoteCollection = $salesCollection;

        return $salesCollection;
    }

    /**
     * @param int $num
     * @param int $storeId
     *
     * @return mixed
     */
    public function getLostBasketCustomerCampaignId($num, $storeId)
    {
        return $this->scopeConfig->getValue(
            constant('self::XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_' . $num),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Send email only if the interval limit passed, no emails during this interval.
     * Return false for any found for this period.
     *
     * @param string $email
     * @param int $storeId
     *
     * @return bool
     */
    public function isIntervalCampaignFound($email, $storeId)
    {
        $cartLimit = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ABANDONED_CART_LIMIT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        //no limit is set skip
        if (! $cartLimit) {
            return false;
        }

        $fromTime = $this->timeZone->scopeDate($storeId, 'now', true);
        $toTime = clone $fromTime;
        $interval = \DateInterval::createFromDateString($cartLimit . ' hours');
        $fromTime->sub($interval);

        $fromDate   = $fromTime->getTimestamp();
        $toDate     = $toTime->getTimestamp();
        $updated = [
            'from' => $fromDate,
            'to' => $toDate,
            'date' => true,
        ];

        //total campaigns sent for this interval of time
        $campaignLimit = $this->campaignCollection->create()
            ->getNumberOfCampaignsForContactByInterval($email, $updated);

        //found campaign
        if ($campaignLimit) {
            return true;
        }

        return false;
    }

    /**
     * @param int $num
     * @param int $storeId
     *
     * @return bool
     */
    public function isLostBasketGuestEnabled($num, $storeId)
    {
        return $this->scopeConfig->isSetFlag(
            constant('self::XML_PATH_LOSTBASKET_GUEST_ENABLED_' . $num),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int $num
     * @param int $storeId
     *
     * @return mixed
     */
    public function getLostBasketSendAfterForGuest($num, $storeId)
    {
        return $this->scopeConfig->getValue(
            constant('self::XML_PATH_LOSTBASKET_GUEST_INTERVAL_' . $num),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int $num
     * @param int $storeId
     *
     * @return mixed
     */
    public function getLostBasketGuestCampaignId($num, $storeId)
    {
        return $this->scopeConfig->getValue(
            constant('self::XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_' . $num),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int $storeId
     *
     * @return null
     */
    private function searchForGuestAbandonedCarts($storeId)
    {
        /*
         * Guests campaigns
         */
        foreach ($this->lostBasketGuests as $num) {
            if ($this->isLostBasketGuestEnabled($num, $storeId)) {
                $sendAfter = $this->getSendAfterIntervalForGuest($storeId, $num);
                $fromTime = new \DateTime('now', new \DateTimezone('UTC'));
                $fromTime->sub($sendAfter);
                $toTime = clone $fromTime;
                $fromTime->sub(\DateInterval::createFromDateString('5 minutes'));

                //format time
                $fromDate   = $fromTime->format('Y-m-d H:i:s');
                $toDate     = $toTime->format('Y-m-d H:i:s');

                //active guest quotes
                $quoteCollection = $this->getStoreQuotes(
                    $fromDate,
                    $toDate,
                    true,
                    $storeId
                );

                //log the time for carts found
                if ($quoteCollection->getSize()) {
                    $this->helper->log('Found guest cart : ' . $num . ', from : ' . $fromDate . ' ,to : ' . $toDate);
                }
                $guestCampaignId = $this->getLostBasketGuestCampaignId($num, $storeId);

                foreach ($quoteCollection as $quote) {
                    $email = $quote->getCustomerEmail();
                    $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
                    $quoteId = $quote->getId();
                    // update contact last quote_id
                    $this->helper->updateLastQuoteId($quoteId, $email, $websiteId);
                    // update abandoned product name for contact
                    $items = $quote->getAllItems();
                    $mostExpensiveItem = false;
                    foreach ($items as $item) {
                        if ($mostExpensiveItem == false) {
                            $mostExpensiveItem = $item;
                        } elseif ($item->getPrice() > $mostExpensiveItem->getPrice()) {
                            $mostExpensiveItem = $item;
                        }
                    }
                    //api- set the most expensive product to datafield
                    $this->processMostExpensiveItem($mostExpensiveItem, $email, $websiteId);

                    //no emails during this period of time for a contact
                    if ($this->isIntervalCampaignFound($email, $storeId)) {
                        return;
                    }
                    $item = $this->campaignFactory->create()
                        ->setEmail($email)
                        ->setEventName('Lost Basket')
                        ->setQuoteId($quoteId)
                        ->setCheckoutMethod('Guest')
                        ->setMessage('Guest Abandoned Cart ' . $num)
                        ->setCampaignId($guestCampaignId)
                        ->setStoreId($storeId)
                        ->setWebsiteId($websiteId)
                        ->setIsSent(null);

                    $this->campaignResource->saveItem($item);
                    $this->totalGuests++;
                }
            }
        }
    }

    /**
     * @param int $storeId
     *
     * @return null
     */
    private function searchForCustomerAbandonedCarts($storeId)
    {
        /*
         * Customers campaigns
         */
        foreach ($this->lostBasketCustomers as $num) {
            //customer enabled
            if ($this->isLostBasketCustomerEnabled($num, $storeId)) {
                //hit the first AC using minutes
                $interval = $this->getInterval($storeId, $num);

                $fromTime = new \DateTime('now', new \DateTimezone('UTC'));
                $fromTime->sub($interval);
                $toTime = clone $fromTime;
                $fromTime->sub(\DateInterval::createFromDateString('5 minutes'));

                //format time
                $fromDate = $fromTime->format('Y-m-d H:i:s');
                $toDate = $toTime->format('Y-m-d H:i:s');

                //active quotes
                $quoteCollection = $this->getStoreQuotes($fromDate, $toDate, false, $storeId);
                //found abandoned carts
                if ($quoteCollection->getSize()) {
                    $this->helper->log('Customer cart : ' . $num . ', from : ' . $fromDate . ' ,to ' . $toDate);
                }

                //campaign id for customers
                $campaignId = $this->getLostBasketCustomerCampaignId($num, $storeId);

                foreach ($quoteCollection as $quote) {
                    $email = $quote->getCustomerEmail();
                    $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

                    $quoteId = $quote->getId();
                    //api - set the last quote id for customer
                    $this->helper->updateLastQuoteId($quoteId, $email, $websiteId);

                    $items = $quote->getAllItems();
                    $mostExpensiveItem = false;
                    foreach ($items as $item) {
                        if ($mostExpensiveItem == false) {
                            $mostExpensiveItem = $item;
                        } elseif ($item->getPrice() > $mostExpensiveItem->getPrice()) {
                            $mostExpensiveItem = $item;
                        }
                    }
                    //api-send the most expensive product for abandoned cart
                    $this->processMostExpensiveItem($mostExpensiveItem, $email, $websiteId);

                    //send email only if the interval limit passed, no emails during this interval
                    if ($this->isIntervalCampaignFound($email, $storeId)) {
                        return;
                    }

                    //save lost basket for sending
                    $item = $this->campaignFactory->create()
                        ->setEmail($email)
                        ->setCustomerId($quote->getCustomerId())
                        ->setEventName('Lost Basket')
                        ->setQuoteId($quoteId)
                        ->setMessage('Abandoned Cart ' . $num)
                        ->setCampaignId($campaignId)
                        ->setStoreId($storeId)
                        ->setWebsiteId($websiteId)
                        ->setIsSent(null);

                    $this->campaignResource->saveItem($item);

                    $this->totalCustomers++;
                }
            }
        }
    }

    /**
     * @param int $storeId
     * @param int $num
     *
     * @return \DateInterval
     */
    private function getInterval($storeId, $num)
    {
        if ($num == 1) {
            $minutes = $this->getLostBasketCustomerInterval($num, $storeId);
            $interval = \DateInterval::createFromDateString($minutes . ' minutes');
        } else {
            $hours = (int)$this->getLostBasketCustomerInterval($num, $storeId);
            $interval = \DateInterval::createFromDateString($hours . ' hours');
        }
        return $interval;
    }

    /**
     * @param mixed $mostExpensiveItem
     * @param string $email
     * @param int $websiteId
     *
     * @return null
     */
    private function processMostExpensiveItem($mostExpensiveItem, $email, $websiteId)
    {
        if ($mostExpensiveItem) {
            $this->helper->updateAbandonedProductName($mostExpensiveItem->getName(), $email, $websiteId);
        }
    }

    /**
     * @param int $storeId
     * @param int $num
     *
     * @return \DateInterval
     */
    protected function getSendAfterIntervalForGuest($storeId, $num)
    {
        $timeInterval = $this->getLostBasketSendAfterForGuest($num, $storeId);

        //for the  first cart which use the minutes
        if ($num == 1) {
            $interval = \DateInterval::createFromDateString($timeInterval . ' minutes');
        } else {
            $interval = \DateInterval::createFromDateString($timeInterval . ' hours');
        }

        return $interval;
    }
}
