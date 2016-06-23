<?php

namespace Dotdigitalgroup\Email\Model\Sales;

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
    protected $_helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $_quoteCollection;
    /**
     * @var \Dotdigitalgroup\Email\Model\CampaignFactory
     */
    protected $_campaignFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory
     */
    protected $_campaignCollection;
    /**
     * @var \Dotdigitalgroup\Email\Model\RulesFactory
     */
    protected $_rulesFactory;

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
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignCollection,
        \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $collectionFactory
    ) {
        $this->_rulesFactory = $rulesFactory;
        $this->_helper = $helper;
        $this->_campaignCollection = $campaignCollection;
        $this->_campaignFactory = $campaignFactory;
        $this->_storeManager = $storeManager;
        $this->_quoteCollection = $collectionFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Proccess abandoned carts.
     * 
     * @param string $mode
     */
    public function proccessAbandonedCarts($mode = 'all')
    {
        /*
         * Save lost baskets to be send in Send table.
         */
        $stores = $this->_helper->getStores();
        foreach ($stores as $store) {
            $storeId = $store->getId();
            if ($mode == 'all' || $mode == 'customers') {
                /*
                 * Customers campaigns
                 */
                foreach ($this->lostBasketCustomers as $num) {
                    //customer enabled
                    if ($this->_isLostBasketCustomerEnabled($num, $storeId)) {
                        //number of the campaign use minutes
                        if ($num == 1) {
                            $minutes = $this->_getLostBasketCustomerInterval(
                                $num, $storeId
                            );
                            $interval = new \DateInterval(
                                'PT' . $minutes . 'M'
                            );
                        } else {
                            $hours = (int)$this->_getLostBasketCustomerInterval(
                                $num, $storeId
                            );
                            $interval = new \DateInterval('PT' . $hours . 'H');
                        }

                        $fromTime = new \DateTime(
                            'now', new \DateTimeZone('UTC')
                        );
                        $fromTime->sub($interval);
                        $toTime = clone $fromTime;
                        $fromTime->sub(new \DateInterval('PT5M'));

                        //format time
                        $fromDate = $fromTime->format('Y-m-d H:i:s');
                        $toDate = $toTime->format('Y-m-d H:i:s');

                        //active quotes
                        $quoteCollection = $this->_getStoreQuotes(
                            $fromDate, $toDate, $guest = false, $storeId
                        );
                        //found abandoned carts
                        if ($quoteCollection->getSize()) {
                            $this->_helper->log(
                                'Customer cart : ' . $num . ', from : '
                                . $fromDate . ' ,to ' . $toDate
                            );
                        }

                        //campaign id for customers
                        $campaignId = $this->_getLostBasketCustomerCampaignId(
                            $num, $storeId
                        );
                        foreach ($quoteCollection as $quote) {
                            $email = $quote->getCustomerEmail();
                            $websiteId = $store->getWebsiteId();
                            $quoteId = $quote->getId();
                            //api - set the last quote id for customer
                            $this->_helper->updateLastQuoteId(
                                $quoteId, $email, $websiteId
                            );

                            $items = $quote->getAllItems();
                            $mostExpensiveItem = false;
                            foreach ($items as $item) {
                                if ($mostExpensiveItem == false) {
                                    $mostExpensiveItem = $item;
                                } elseif ($item->getPrice()
                                    > $mostExpensiveItem->getPrice()
                                ) {
                                    $mostExpensiveItem = $item;
                                }
                            }
                            //api-send the most expensive product for abandoned cart
                            if ($mostExpensiveItem) {
                                $this->_helper->updateAbandonedProductName(
                                    $mostExpensiveItem->getName(), $email,
                                    $websiteId
                                );
                            }

                            //send email only if the interval limit passed, no emails during this interval
                            $intervalLimit = $this->_checkCustomerCartLimit(
                                $email, $storeId
                            );
                            //no campign found for interval pass
                            if (!$intervalLimit) {
                                //save lost basket for sending
                                //@codingStandardsIgnoreStart
                                $this->_campaignFactory->create()
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
                            }
                        }
                    }
                }
            }
            if ($mode == 'all' || $mode == 'guests') {
                /*
                 * Guests campaigns
                 */
                foreach ($this->lostBasketGuests as $num) {
                    if ($this->_isLostBasketGuestEnabled($num, $storeId)) {
                        //for the  first cart which use the minutes
                        if ($num == 1) {
                            $minutes = $this->_getLostBasketGuestIterval(
                                $num, $storeId
                            );
                            $interval = new \DateInterval(
                                'PT' . $minutes . 'M'
                            );
                        } else {
                            $hours = $this->_getLostBasketGuestIterval(
                                $num, $storeId
                            );
                            $interval = new \DateInterval('PT' . $hours . 'H');
                        }

                        $fromTime = new \DateTime(
                            'now', new \DateTimeZone('UTC')
                        );
                        $fromTime->sub($interval);
                        $toTime = clone $fromTime;
                        $fromTime->sub(new \DateInterval('PT5M'));

                        //format time
                        $fromDate = $fromTime->format('Y-m-d H:i:s');
                        $toDate = $toTime->format('Y-m-d H:i:s');

                        //active guest quotes
                        $quoteCollection = $this->_getStoreQuotes(
                            $fromDate, $toDate, $guest = true, $storeId
                        );
                        //log the time for carts found
                        if ($quoteCollection->getSize()) {
                            $this->_helper->log(
                                'Guest cart : ' . $num . ', from : ' . $fromDate
                                . ' ,to : ' . $toDate
                            );
                        }
                        $guestCampaignId = $this->_getLostBasketGuestCampaignId(
                            $num, $storeId
                        );
                        foreach ($quoteCollection as $quote) {
                            $email = $quote->getCustomerEmail();
                            $websiteId = $store->getWebsiteId();
                            $quoteId = $quote->getId();
                            // upate last quote id for the contact
                            $this->_helper->updateLastQuoteId(
                                $quoteId, $email, $websiteId
                            );
                            // update abandoned product name for contact
                            $items = $quote->getAllItems();
                            $mostExpensiveItem = false;
                            foreach ($items as $item) {
                                if ($mostExpensiveItem == false) {
                                    $mostExpensiveItem = $item;
                                } elseif ($item->getPrice()
                                    > $mostExpensiveItem->getPrice()
                                ) {
                                    $mostExpensiveItem = $item;
                                }
                            }
                            //api- set the most expensive product to datafield
                            if ($mostExpensiveItem) {
                                $this->_helper->updateAbandonedProductName(
                                    $mostExpensiveItem->getName(), $email,
                                    $websiteId
                                );
                            }

                            //send email only if the interval limit passed, no emails during this interval
                            $campignFound = $this->_checkCustomerCartLimit(
                                $email, $storeId
                            );

                            //no campign found for interval pass
                            if (!$campignFound) {
                                //save lost basket for sending
                                //@codingStandardsIgnoreStart
                                $this->_campaignFactory->create()
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
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $num
     * @param $storeId
     *
     * @return mixed
     */
    protected function _isLostBasketCustomerEnabled($num, $storeId)
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
    protected function _getLostBasketCustomerInterval($num, $storeId)
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
    protected function _getStoreQuotes(
        $from = null,
        $to = null,
        $guest = false,
        $storeId = 0
    ) {
        $updated = [
            'from' => $from,
            'to' => $to,
            'date' => true,
        ];

        $salesCollection = $this->_quoteCollection->create()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('items_count', ['gt' => 0])
            ->addFieldToFilter('customer_email', ['neq' => ''])
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('main_table.updated_at', $updated);
        //guests
        if ($guest) {
            $salesCollection->addFieldToFilter(
                'main_table.customer_id', ['null' => true]
            );
        } else {
            //customers
            $salesCollection->addFieldToFilter(
                'main_table.customer_id', ['notnull' => true]
            );
        }

        //process rules on collection
        $ruleModel = $this->_rulesFactory->create();
        $websiteId = $this->_storeManager->getStore($storeId)
            ->getWebsiteId();
        $salesCollection = $ruleModel->process(
            $salesCollection, \Dotdigitalgroup\Email\Model\Rules::ABANDONED,
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
    protected function _getLostBasketCustomerCampaignId($num, $storeId)
    {
        return $this->scopeConfig->getValue(
            constant('self::XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_' . $num),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check customer campaign that was sent by a limit from config.
     * Return false for any found for this period.
     *
     * @param $email
     * @param $storeId
     *
     * @return bool
     */
    protected function _checkCustomerCartLimit($email, $storeId)
    {
        $cartLimit = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_ABANDONED_CART_LIMIT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        //no limit is set skip
        if (!$cartLimit) {
            return false;
        }

        $fromTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $toTime = clone $fromTime;
        $interval = new \DateInterval('PT' . $cartLimit . 'H');
        $fromTime->sub($interval);

        $fromDate = $fromTime->getTimestamp();
        $toDate = $toTime->getTimestamp();
        $updated = [
            'from' => $fromDate,
            'to' => $toDate,
            'date' => true,
        ];

        //total campaigns sent for this interval of time
        $campaignLimit = $this->_campaignCollection->create()
            ->getCollection()
            ->addFieldToFilter('email', $email)
            ->addFieldToFilter('event_name', 'Lost Basket')
            ->addFieldToFilter('sent_at', $updated)
            ->count();

        //no campigns found
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
    protected function _isLostBasketGuestEnabled($num, $storeId)
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
    protected function _getLostBasketGuestIterval($num, $storeId)
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
    protected function _getLostBasketGuestCampaignId($num, $storeId)
    {
        return $this->scopeConfig->getValue(
            constant('self::XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_' . $num),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
