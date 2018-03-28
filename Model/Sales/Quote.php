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

    const CUSTOMER_LOST_BASKET_ONE = 1;
    const CUSTOMER_LOST_BASKET_TWO = 2;
    const CUSTOMER_LOST_BASKET_THREE = 3;

    const GUEST_LOST_BASKET_ONE = 1;
    const GUEST_LOST_BASKET_TWO = 2;
    const GUEST_LOST_BASKET_THREE = 3;

    /**
     * @var \Dotdigitalgroup\Email\Model\Abandoned
     */
    public $abandonedFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory
     */
    public $abandonedCollectionFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    public $quoteCollectionFactory;

    /**
     * @var Campaign
     */
    private $campaignResource;

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
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned
     */
    private $abandonedResource;

    /**
     * Quote constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AbandonedFactory $abandonedFactory
     * @param \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory
     * @param Campaign\CollectionFactory $campaignCollection
     * @param Campaign $campaignResource
     * @param \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory $abandonedCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned $abandonedResource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AbandonedFactory $abandonedFactory,
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignCollection,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign $campaignResource,
        \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory $abandonedCollectionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned $abandonedResource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->rulesFactory = $rulesFactory;
        $this->helper = $helper;
        $this->abandonedFactory = $abandonedFactory;
        $this->abandonedCollectionFactory = $abandonedCollectionFactory;
        $this->abandonedResource = $abandonedResource;
        $this->campaignCollection = $campaignCollection;
        $this->campaignResource = $campaignResource;
        $this->campaignFactory = $campaignFactory;
        $this->storeManager = $storeManager;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->orderCollection = $collectionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->timeZone = $timezone;
    }

    /**
     * Process abandoned carts.
     *
     * @return array
     */
    public function processAbandonedCarts()
    {
        $result = [];
        $stores = $this->helper->getStores();

        foreach ($stores as $store) {
            $storeId = $store->getId();
            $websiteId = $store->getWebsiteId();
            $secondCustomerEnabled = $this->isLostBasketCustomerEnabled(self::CUSTOMER_LOST_BASKET_TWO, $storeId);
            $thirdCustomerEnabled = $this->isLostBasketCustomerEnabled(self::CUSTOMER_LOST_BASKET_THREE, $storeId);
            $secondGuestEnabled = $this->isLostBasketGuestEnabled(self::GUEST_LOST_BASKET_TWO, $storeId);
            $thirdGuestEnabled = $this->isLostBasketGuestEnabled(self::GUEST_LOST_BASKET_THREE, $storeId);
            //customer
            if ($this->isLostBasketCustomerEnabled(self::CUSTOMER_LOST_BASKET_ONE, $storeId) ||
                $secondCustomerEnabled ||
                $thirdCustomerEnabled
            ) {
                $result[$storeId]['firstCustomer'] = $this->processCustomerFirstAbandonedCart($storeId);
            }

            //second customer
            if ($secondCustomerEnabled) {
                $result[$storeId]['secondCustomer'] = $this->processExistingAbandonedCart(
                    $this->getLostBasketCustomerCampaignId(self::CUSTOMER_LOST_BASKET_TWO, $storeId),
                    $storeId,
                    $websiteId,
                    self::CUSTOMER_LOST_BASKET_TWO
                );
            }

            //third customer
            if ($thirdCustomerEnabled) {
                $result[$storeId]['thirdCustomer'] = $this->processExistingAbandonedCart(
                    $this->getLostBasketCustomerCampaignId(self::CUSTOMER_LOST_BASKET_THREE, $storeId),
                    $storeId,
                    $websiteId,
                    self::CUSTOMER_LOST_BASKET_THREE
                );
            }

            //guest
            if ($this->isLostBasketGuestEnabled(self::GUEST_LOST_BASKET_ONE, $storeId) ||
                $secondGuestEnabled ||
                $thirdGuestEnabled
            ) {
                $result[$storeId]['firstGuest'] = $this->processGuestFirstAbandonedCart($storeId);
            }
            //second guest
            if ($secondGuestEnabled) {
                $result[$storeId]['secondGuest'] = $this->processExistingAbandonedCart(
                    $this->getLostBasketGuestCampaignId(self::GUEST_LOST_BASKET_TWO, $storeId),
                    $storeId,
                    $websiteId,
                    self::GUEST_LOST_BASKET_TWO,
                    true
                );
            }
            //third guest
            if ($thirdGuestEnabled) {
                $result[$storeId]['thirdGuest'] = $this->processExistingAbandonedCart(
                    $this->getLostBasketGuestCampaignId(self::GUEST_LOST_BASKET_THREE, $storeId),
                    $storeId,
                    $websiteId,
                    self::GUEST_LOST_BASKET_THREE,
                    true
                );
            }
        }

        return $result;
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
     * @param bool $guest
     * @param int $storeId
     *
     * @return mixed
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

    /**
     * @param $storeId
     * @return int|string
     */
    private function processCustomerFirstAbandonedCart($storeId)
    {
        $result = 0;
        $abandonedNum = 1;
        $interval = $this->getInterval($storeId, $abandonedNum);
        $fromTime = new \DateTime('now', new \DateTimezone('UTC'));
        $fromTime->sub($interval);
        $toTime = clone $fromTime;
        $fromTime->sub(\DateInterval::createFromDateString('5 minutes'));
        $fromDate = $fromTime->format('Y-m-d H:i:s');
        $toDate = $toTime->format('Y-m-d H:i:s');

        //active quotes
        $quoteCollection = $this->getStoreQuotes($fromDate, $toDate, false, $storeId);

        //found abandoned carts
        if ($quoteCollection->getSize()) {
            $this->helper->log('Customer AC 1 ' . $fromDate . ' - ' . $toDate);
        }

        //campaign id for customers
        $campaignId = $this->getLostBasketCustomerCampaignId($abandonedNum, $storeId);
        foreach ($quoteCollection as $quote) {
            $quoteId = $quote->getId();
            $items = $quote->getAllItems();
            $email = $quote->getCustomerEmail();
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
            $itemIds = $this->getQuoteItemIds($items);

            if ($mostExpensiveItem = $this->getMostExpensiveItems($items)) {
                $this->helper->updateAbandonedProductName(
                    $mostExpensiveItem->getName(),
                    $email,
                    $websiteId
                );
            }

            $abandonedModel = $this->abandonedFactory->create()
                ->loadByQuoteId($quoteId);

            if ($this->abandonedCartAlreadyExists($abandonedModel) &&
                $this->shouldNotSendACAgain($abandonedModel, $quote)) {
                if ($this->shouldDeleteAbandonedCart($quote)) {
                    $this->deleteAbandonedCart($abandonedModel);
                }
                continue;
            }

            //create abandoned cart
            $this->createAbandonedCart($abandonedModel, $quote, $itemIds);

            //send campaign; check if valid to be sent
            if ($this->isLostBasketCustomerEnabled(self::CUSTOMER_LOST_BASKET_ONE, $storeId)) {
                $this->sendEmailCampaign($email, $quote, $campaignId, self::CUSTOMER_LOST_BASKET_ONE, $websiteId);
            }

            $this->totalCustomers++;
            $result = $this->totalCustomers;
        }

        return $result;
    }

    /**
     * @param $allItemsIds
     * @return array
     */
    private function getQuoteItemIds($allItemsIds)
    {
        $itemIds = [];
        foreach ($allItemsIds as $item) {
            $itemIds[] = $item->getProductId();
        }

        return $itemIds;
    }

    /**
     * @param $items
     * @return bool|\Magento\Quote\Model\Quote\Item
     */
    private function getMostExpensiveItems($items)
    {
        $mostExpensiveItem = false;
        foreach ($items as $item) {
            /** @var $item \Magento\Quote\Model\Quote\Item */
            if ($mostExpensiveItem == false) {
                $mostExpensiveItem = $item;
            } elseif ($item->getPrice() > $mostExpensiveItem->getPrice()) {
                $mostExpensiveItem = $item;
            }
        }

        return $mostExpensiveItem;
    }

    /**
     * @param $quote
     * @param $abandonedModel
     * @return bool
     */
    private function isItemsChanged($quote, $abandonedModel)
    {
        if ($quote->getItemsCount() != $abandonedModel->getItemsCount()) {
            return true;
        } else {
            //number of items matches
            $quoteItemIds = $this->getQuoteItemIds($quote->getAllItems());
            $abandonedItemIds = explode(',', $abandonedModel->getItemsIds());

            //quote items not same
            if (! $this->isItemsIdsSame($quoteItemIds, $abandonedItemIds)) {
                return true;
            }

            return false;
        }
    }

    /**
     * @param $abandonedModel
     * @param $quote
     * @param $itemIds
     */
    private function createAbandonedCart($abandonedModel, $quote, $itemIds)
    {
        $abandonedModel->setStoreId($quote->getStoreId())
            ->setCustomerId($quote->getCustomerId())
            ->setEmail($quote->getCustomerEmail())
            ->setQuoteId($quote->getId())
            ->setQuoteUpdatedAt($quote->getUpdatedAt())
            ->setAbandonedCartNumber(1)
            ->setItemsCount($quote->getItemsCount())
            ->setItemsIds(implode(',', $itemIds))
            ->save();
    }

    /**
     * @param $email
     * @param $quote
     * @param $campaignId
     * @param $number
     * @param $websiteId
     */
    private function sendEmailCampaign($email, $quote, $campaignId, $number, $websiteId)
    {
        $storeId = $quote->getStoreId();
        //interval campaign found
        if ($this->isIntervalCampaignFound($email, $storeId) || ! $campaignId) {
            return;
        }
        $customerId = $quote->getCustomerId();
        $message = ($customerId)? 'Abandoned Cart ' . $number : 'Guest Abandoned Cart ' . $number;
        $campaign = $this->campaignFactory->create()
            ->setEmail($email)
            ->setCustomerId($customerId)
            ->setEventName(\Dotdigitalgroup\Email\Model\Campaign::CAMPAIGN_EVENT_LOST_BASKET)
            ->setQuoteId($quote->getId())
            ->setMessage($message)
            ->setCampaignId($campaignId)
            ->setStoreId($storeId)
            ->setWebsiteId($websiteId)
            ->setSendStatus(\Dotdigitalgroup\Email\Model\Campaign::PENDING);

        $this->campaignResource->save($campaign);
    }

    /**
     * @param $storeId
     * @return int
     */
    private function processGuestFirstAbandonedCart($storeId)
    {
        $result = 0;
        $abandonedNum = 1;

        $sendAfter = $this->getSendAfterIntervalForGuest($storeId, $abandonedNum);
        $fromTime = new \DateTime('now', new \DateTimezone('UTC'));
        $fromTime->sub($sendAfter);
        $toTime = clone $fromTime;
        $fromTime->sub(\DateInterval::createFromDateString('5 minutes'));

        //format time
        $fromDate   = $fromTime->format('Y-m-d H:i:s');
        $toDate     = $toTime->format('Y-m-d H:i:s');

        $quoteCollection = $this->getStoreQuotes($fromDate, $toDate, true, $storeId);
        if ($quoteCollection->getSize()) {
            $this->helper->log('Guest AC 1 ' . $fromDate . ' - ' . $toDate);
        }

        $guestCampaignId = $this->getLostBasketGuestCampaignId($abandonedNum, $storeId);
        foreach ($quoteCollection as $quote) {
            $quoteId = $quote->getId();
            $items = $quote->getAllItems();
            $email = $quote->getCustomerEmail();
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
            $itemIds = $this->getQuoteItemIds($items);

            if ($mostExpensiveItem = $this->getMostExpensiveItems($items)) {
                $this->helper->updateAbandonedProductName(
                    $mostExpensiveItem->getName(),
                    $email,
                    $websiteId
                );
            }

            $abandonedModel = $this->abandonedFactory->create()
                ->loadByQuoteId($quoteId);

            if ($this->abandonedCartAlreadyExists($abandonedModel) &&
                $this->shouldNotSendACAgain($abandonedModel, $quote)) {
                if ($this->shouldDeleteAbandonedCart($quote)) {
                    $this->deleteAbandonedCart($abandonedModel);
                }
                continue;
            }
            //create abandoned cart
            $this->createAbandonedCart($abandonedModel, $quote, $itemIds);

            //send campaign; check if still valid to be sent
            if ($this->isLostBasketGuestEnabled(self::GUEST_LOST_BASKET_ONE, $storeId)) {
                $this->sendEmailCampaign($email, $quote, $guestCampaignId, self::GUEST_LOST_BASKET_ONE, $websiteId);
            }

            $this->totalGuests++;
            $result = $this->totalGuests;
        }

        return $result;
    }

    /**
     * @param $abandonedModel
     *
     * @return mixed
     */
    private function abandonedCartAlreadyExists($abandonedModel)
    {
        return $abandonedModel->getId();
    }

    /**
     * @param $quote
     * @return bool
     */
    private function shouldNotSendACAgain($abandonedModel, $quote)
    {
        return
            !$quote->getIsActive() ||
            $quote->getItemsCount() == 0 ||
            !$this->isItemsChanged($quote, $abandonedModel);
    }

    /**
     * @param $quote
     *
     * @return bool
     */
    private function shouldDeleteAbandonedCart($quote)
    {
        return !$quote->getIsActive() || $quote->getItemsCount() == 0;
    }

    /**
     * @param $abandonedModel
     * @throws \Exception
     */
    private function deleteAbandonedCart($abandonedModel)
    {
        $this->abandonedResource->delete($abandonedModel);
    }

    /**
     * @param $campaignId
     * @param $storeId
     * @param $websiteId
     * @param $number
     * @param bool $guest
     *
     * @return int
     */
    private function processExistingAbandonedCart($campaignId, $storeId, $websiteId, $number, $guest = false)
    {
        $result = 0;
        $fromTime = new \DateTime('now', new \DateTimezone('UTC'));
        if ($guest) {
            $interval = $this->getSendAfterIntervalForGuest($storeId, $number);
            $message = 'Guest';
        } else {
            $interval = $this->getInterval($storeId, $number);
            $message = 'Customer';
        }

        $fromTime->sub($interval);
        $toTime = clone $fromTime;
        $fromTime->sub(\DateInterval::createFromDateString('5 minutes'));
        $fromDate   = $fromTime->format('Y-m-d H:i:s');
        $toDate     = $toTime->format('Y-m-d H:i:s');
        //get abandoned carts already sent
        $abandonedCollection = $this->getAbandonedCartsForStore(
            $number,
            $fromDate,
            $toDate,
            $storeId,
            $guest
        );

        //quote collection based on the updated date from abandoned cart table
        $quoteIds = $abandonedCollection->getColumnValues('quote_id');
        if (empty($quoteIds)) {
            return $result;
        }
        $quoteCollection = $this->getProcessedQuoteByIds($quoteIds, $storeId);

        //found abandoned carts
        if ($quoteCollection->getSize()) {
            $this->helper->log(
                $message . ' Abandoned Cart ' . $number . ',from ' . $fromDate . '  :  ' . $toDate . ', storeId '
                . $storeId
            );
        }

        foreach ($quoteCollection as $quote) {
            $quoteId = $quote->getId();
            $email = $quote->getCustomerEmail();

            if ($mostExpensiveItem = $this->getMostExpensiveItems($quote->getAllItems())) {
                $this->helper->updateAbandonedProductName(
                    $mostExpensiveItem->getName(),
                    $email,
                    $websiteId
                );
            }

            $abandonedModel = $this->abandonedFactory->create()
                ->loadByQuoteId($quoteId);
            //number of items changed or not active anymore
            if ($this->isItemsChanged($quote, $abandonedModel)) {
                if ($this->shouldDeleteAbandonedCart($quote)) {
                    $this->deleteAbandonedCart($abandonedModel);
                }
                continue;
            }

            $abandonedModel->setAbandonedCartNumber($number)
                ->setQuoteUpdatedAt($quote->getUpdatedAt())
                ->save();

            $this->sendEmailCampaign($email, $quote, $campaignId, $number, $websiteId);
            $result++;
        }

        return $result;
    }

    /**
     * @param $number
     * @param $from
     * @param $to
     * @param $storeId
     * @param bool $guest
     * @return mixed
     */
    private function getAbandonedCartsForStore($number, $from, $to, $storeId, $guest = false)
    {
        $updated = [
            'from' => $from,
            'to'   => $to,
            'date' => true
        ];

        $abandonedCollection = $this->abandonedCollectionFactory->create()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('abandoned_cart_number', --$number)
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('quote_updated_at', $updated);

        if ($guest) {
            $abandonedCollection->addFieldToFilter('customer_id', ['null' => true]);
        } else {
            $abandonedCollection->addFieldToFilter('customer_id', ['notnull' => true]);
        }

        return $abandonedCollection;
    }

    /**
     * @param $quoteIds
     * @param $storeId
     * @return mixed
     */
    private function getProcessedQuoteByIds($quoteIds, $storeId)
    {
        $quoteCollection = $this->quoteCollectionFactory->create()
            ->addFieldToFilter('entity_id', ['in' => $quoteIds]);

        //process rules on collection
        $ruleModel       = $this->rulesFactory->create();
        $quoteCollection = $ruleModel->process(
            $quoteCollection,
            \Dotdigitalgroup\Email\Model\Rules::ABANDONED,
            $this->storeManager->getStore($storeId)->getWebsiteId()
        );

        return $quoteCollection;
    }

    /**
     * Compare items ids.
     *
     * @param $quoteItemIds
     * @param $abandonedItemIds
     * @return bool
     */
    private function isItemsIdsSame($quoteItemIds, $abandonedItemIds)
    {
        return $quoteItemIds == $abandonedItemIds;
    }
}
