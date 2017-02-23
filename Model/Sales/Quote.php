<?php

namespace Dotdigitalgroup\Email\Model\Sales;

// @codingStandardsIgnoreFile

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
     * Number of lost baskets available.
     *
     * @var array
     */
    public $lostBasketCustomers = [1, 2, 3];
    /**
     * Number of guest lost baskets available.
     *
     * @var array
     */
    public $lostBasketGuests = [1, 2, 3];

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    public $quoteCollection;
    /**
     * @var \Dotdigitalgroup\Email\Model\CampaignFactory
     */
    public $campaignFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory
     */
    public $campaignCollection;
    /**
     * @var \Dotdigitalgroup\Email\Model\RulesFactory
     */
    public $rulesFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public $timeZone;

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
     *
     * @param \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignCollection
     * @param \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignCollection,
        \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->rulesFactory = $rulesFactory;
        $this->helper = $helper;
        $this->campaignCollection = $campaignCollection;
        $this->campaignFactory = $campaignFactory;
        $this->storeManager = $storeManager;
        $this->quoteCollection = $collectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->timeZone = $timezone;
    }

    /**
     * Proccess abandoned carts.
     *
     * @param string $mode
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
     * @param $num
     * @param $storeId
     *
     * @return mixed
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
     * @param $num
     * @param $storeId
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
     * @param null $from
     * @param null $to
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

        $salesCollection = $this->quoteCollection->create();

        $salesCollection->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('items_count', ['gt' => 0])
            ->addFieldToFilter('customer_email', ['neq' => ''])
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('main_table.updated_at', $updated);
        //guests
        if ($guest) {
            $salesCollection->addFieldToFilter('main_table.customer_id', ['null' => true]);
        } else {
            //customers
            $salesCollection->addFieldToFilter('main_table.customer_id', ['notnull' => true]);
        }

        //process rules on collection
        $ruleModel = $this->rulesFactory->create();
        $websiteId = $this->storeManager->getStore($storeId)
            ->getWebsiteId();
        $salesCollection = $ruleModel->process(
            $salesCollection,
            \Dotdigitalgroup\Email\Model\Rules::ABANDONED,
            $websiteId
        );

        return $salesCollection;
    }

    /**
     * @param $num
     * @param $storeId
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
     * @param $email
     * @param $storeId
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
            ->addFieldToFilter('email', $email)
            ->addFieldToFilter('event_name', 'Lost Basket')
            ->addFieldToFilter('sent_at', $updated)
            ->count();
        //found campaign
        if ($campaignLimit) {
            return true;
        }

        return false;
    }

    /**
     * @param $num
     * @param $storeId
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
     * @param $num
     * @param $storeId
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
     * @param $num
     * @param $storeId
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
     * @param $storeId
     */
    protected function searchForGuestAbandonedCarts($storeId)
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
                    $guest = true,
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
                    if ($mostExpensiveItem) {
                        $this->helper->updateAbandonedProductName($mostExpensiveItem->getName(), $email, $websiteId);
                    }

                    //no emails during this period of time for a contact
                    if ($this->isIntervalCampaignFound($email, $storeId)) {
                        return;
                    }
                    //@codingStandardsIgnoreStart
                    $this->campaignFactory->create()
                        ->setEmail($email)
                        ->setEventName('Lost Basket')
                        ->setQuoteId($quoteId)
                        ->setCheckoutMethod('Guest')
                        ->setMessage('Guest Abandoned Cart ' . $num)
                        ->setCampaignId($guestCampaignId)
                        ->setStoreId($storeId)
                        ->setWebsiteId($websiteId)
                        ->setIsSent(null)
                        ->save();
                    //@codingStandardsIgnoreEnd
                    $this->totalGuests++;
                }
            }
        }
    }

    /**
     * @param $storeId
     */
    protected function searchForCustomerAbandonedCarts($storeId)
    {
        /*
         * Customers campaigns
         */
        foreach ($this->lostBasketCustomers as $num) {
            //customer enabled
            if ($this->isLostBasketCustomerEnabled($num, $storeId)) {
                //hit the first AC using minutes
                if ($num == 1) {
                    $minutes = $this->getLostBasketCustomerInterval($num, $storeId);
                    $interval = \DateInterval::createFromDateString($minutes . ' minutes');
                } else {
                    $hours = (int)$this->getLostBasketCustomerInterval($num, $storeId);
                    $interval = \DateInterval::createFromDateString($hours . ' hours');
                }
                $fromTime = new \DateTime('now', new \DateTimezone('UTC'));
                $fromTime->sub($interval);
                $toTime = clone $fromTime;
                $fromTime->sub(\DateInterval::createFromDateString('5 minutes'));

                //format time
                $fromDate = $fromTime->format('Y-m-d H:i:s');
                $toDate = $toTime->format('Y-m-d H:i:s');

                //active quotes
                $quoteCollection = $this->getStoreQuotes($fromDate, $toDate, $guest = false, $storeId);
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
                    if ($mostExpensiveItem) {
                        $this->helper->updateAbandonedProductName($mostExpensiveItem->getName(), $email, $websiteId);
                    }

                    //send email only if the interval limit passed, no emails during this interval
                    if ($this->isIntervalCampaignFound($email, $storeId)) {
                        return;
                    }

                    //save lost basket for sending
                    //@codingStandardsIgnoreStart
                    $this->campaignFactory->create()
                        ->setEmail($email)
                        ->setCustomerId($quote->getCustomerId())
                        ->setEventName('Lost Basket')
                        ->setQuoteId($quoteId)
                        ->setMessage('Abandoned Cart ' . $num)
                        ->setCampaignId($campaignId)
                        ->setStoreId($storeId)
                        ->setWebsiteId($websiteId)
                        ->setIsSent(null)
                        ->save();
                    //@codingStandardsIgnoreEnd

                    $this->totalCustomers++;
                }
            }
        }
    }

    /**
     * @param $storeId
     * @param $num
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
