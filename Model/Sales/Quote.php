<?php

namespace Dotdigitalgroup\Email\Model\Sales;

use Dotdigitalgroup\Email\Model\AbandonedCart\PendingContactUpdater;
use Dotdigitalgroup\Email\Model\ResourceModel\Campaign;
use Dotdigitalgroup\Email\Model\Sync\SetsSyncFromTime;
use Dotdigitalgroup\Email\Model\AbandonedCart\TimeLimit;

/**
 * Customer and guest Abandoned Carts.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Quote
{
    use SetsSyncFromTime;

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

    const STATUS_PENDING = 'PendingOptIn';
    const STATUS_CONFIRMED = 'Confirmed';
    const STATUS_SENT = 'Sent';
    const STATUS_EXPIRED = 'Expired';

    /**
     * @var \Dotdigitalgroup\Email\Model\AbandonedFactory
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
     * @var \Dotdigitalgroup\Email\Model\DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * @var PendingContactUpdater
     */
    private $acPendingContactUpdater;

    /**
     * @var \Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data
     */
    private $cartInsight;

    /**
     * @var TimeLimit
     */
    private $timeLimit;

    /**
     * Quote constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\AbandonedFactory $abandonedFactory
     * @param \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory
     * @param Campaign $campaignResource
     * @param \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned $abandonedResource
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory
     * @param PendingContactUpdater $pendingContactUpdater
     * @param \Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data $cartInsight
     * @param TimeLimit $timeLimit
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\AbandonedFactory $abandonedFactory,
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        Campaign $campaignResource,
        \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned $abandonedResource,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory,
        PendingContactUpdater $pendingContactUpdater,
        \Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data $cartInsight,
        TimeLimit $timeLimit
    ) {
        $this->timeZone = $timezone;
        $this->rulesFactory = $rulesFactory;
        $this->campaignFactory = $campaignFactory;
        $this->helper = $data;
        $this->abandonedFactory = $abandonedFactory;
        $this->campaignResource = $campaignResource;
        $this->orderCollection = $collectionFactory;
        $this->abandonedResource = $abandonedResource;
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->scopeConfig = $this->helper->getScopeConfig();
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->campaignCollection = $campaignFactory->create()->campaignCollection;
        $this->abandonedCollectionFactory = $abandonedFactory->create()->abandonedCollectionFactory;
        $this->acPendingContactUpdater = $pendingContactUpdater;
        $this->cartInsight = $cartInsight;
        $this->timeLimit = $timeLimit;
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
        $this->acPendingContactUpdater->update();

        foreach ($stores as $store) {
            $storeId = $store->getId();
            $websiteId = $store->getWebsiteId();

            $result = $this->processAbandonedCartsForCustomers($storeId, $websiteId, $result);
            $result = $this->processAbandonedCartsForGuests($storeId, $websiteId, $result);
        }

        return $result;
    }

    /**
     * Process abandoned carts for customer
     *
     * @param int $storeId
     * @param int $websiteId
     * @param array $result
     *
     * @return array
     */
    private function processAbandonedCartsForCustomers($storeId, $websiteId, $result)
    {
        $secondCustomerEnabled = $this->isLostBasketCustomerEnabled(self::CUSTOMER_LOST_BASKET_TWO, $storeId);
        $thirdCustomerEnabled = $this->isLostBasketCustomerEnabled(self::CUSTOMER_LOST_BASKET_THREE, $storeId);

        //first customer
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

        return $result;
    }

    /**
     * Process abandoned carts for guests
     *
     * @param int $storeId
     * @param int $websiteId
     * @param array $result
     *
     * @return array
     */
    private function processAbandonedCartsForGuests($storeId, $websiteId, $result)
    {
        $secondGuestEnabled = $this->isLostBasketGuestEnabled(self::GUEST_LOST_BASKET_TWO, $storeId);
        $thirdGuestEnabled = $this->isLostBasketGuestEnabled(self::GUEST_LOST_BASKET_THREE, $storeId);

        //first guest
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
     * @param string|null $from
     * @param string|null $to
     * @param bool $guest
     * @param int $storeId
     * @return \Magento\Quote\Model\ResourceModel\Quote\Collection|\Magento\Sales\Model\ResourceModel\Order\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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
        $websiteId = $this->helper->storeManager->getStore($storeId)
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
        if (!$updated = $this->timeLimit->getAbandonedCartTimeLimit($storeId)) {
            return false;
        }

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
            $interval = $this->dateIntervalFactory->create(
                ['interval_spec' => sprintf('PT%sM', $minutes)]
            );
        } else {
            $hours = (int)$this->getLostBasketCustomerInterval($num, $storeId);
            $interval = $this->dateIntervalFactory->create(
                ['interval_spec' => sprintf('PT%sH', $hours)]
            );
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
            $interval = $this->dateIntervalFactory->create(
                ['interval_spec' => sprintf('PT%sM', $timeInterval)]
            );
        } else {
            $interval = $this->dateIntervalFactory->create(
                ['interval_spec' => sprintf('PT%sH', $timeInterval)]
            );
        }

        return $interval;
    }

    /**
     * @param int $storeId
     * @return int|string
     */
    private function processCustomerFirstAbandonedCart($storeId)
    {
        $abandonedNum = 1;

        $fromTime = $this->getSyncFromTime($this->getInterval($storeId, $abandonedNum));
        $toTime = clone $fromTime;
        $fromTime->sub($this->dateIntervalFactory->create(['interval_spec' => 'PT5M']));
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

        $result = $this->createCustomerFirstAbandonedCart($quoteCollection, $storeId, $campaignId);
        $result += $this->processConfirmedCustomerAbandonedCart($storeId, $campaignId);

        return $result;
    }

    /**
     * @param $quoteCollection
     * @param $storeId
     * @param $campaignId
     *
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createCustomerFirstAbandonedCart($quoteCollection, $storeId, $campaignId)
    {
        $result = 0;
        foreach ($quoteCollection as $quote) {
            $websiteId = $this->helper->storeManager->getStore($storeId)->getWebsiteId();
            if (! $this->updateDataFieldAndCreateAc($quote, $websiteId, $storeId)) {
                continue;
            }

            //send campaign; check if valid to be sent
            if ($this->isLostBasketCustomerEnabled(self::CUSTOMER_LOST_BASKET_ONE, $storeId)) {
                $this->sendEmailCampaign(
                    $quote->getCustomerEmail(),
                    $quote,
                    $campaignId,
                    self::CUSTOMER_LOST_BASKET_ONE,
                    $websiteId
                );
            }

            $this->totalCustomers++;
            $result = $this->totalCustomers;
        }

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param int $websiteId
     * @param int $storeId
     *
     * @return bool
     */
    private function updateDataFieldAndCreateAc($quote, $websiteId, $storeId)
    {
        $quoteId = $quote->getId();
        $items = $quote->getAllItems();
        $email = $quote->getCustomerEmail();
        $itemIds = $this->getQuoteItemIds($items);
        $abandonedModel = $this->abandonedFactory->create()
            ->loadByQuoteId($quoteId);
        $contact = $this->helper->getOrCreateContact($email, $websiteId);
        if (!$contact) {
            return false;
        }

        $this->cartInsight->send($quote, $storeId);

        if ($contact->status === self::STATUS_PENDING) {
            $this->createAbandonedCart($abandonedModel, $quote, $itemIds, self::STATUS_PENDING);
            return false;
        }
        if ($this->abandonedCartAlreadyExists($abandonedModel) &&
            $this->shouldNotSendACAgain($abandonedModel, $quote) &&
            $this->isNotAConfirmedContact($abandonedModel)
        ) {
            if ($this->shouldDeleteAbandonedCart($quote)) {
                $this->deleteAbandonedCart($abandonedModel);
            }
            return false;
        } else {
            if ($mostExpensiveItem = $this->getMostExpensiveItems($items)) {
                $this->helper->updateAbandonedProductName(
                    $mostExpensiveItem->getName(),
                    $email,
                    $websiteId
                );
            }
            $this->createAbandonedCart($abandonedModel, $quote, $itemIds, self::STATUS_SENT);
            return true;
        }
    }

    /**
     * @param array $allItemsIds
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
     * @param array $items
     * @return bool|\Magento\Quote\Model\Quote\Item
     */
    public function getMostExpensiveItems($items)
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
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Dotdigitalgroup\Email\Model\Abandoned $abandonedModel
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
     * @param \Dotdigitalgroup\Email\Model\Abandoned $abandonedModel
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $itemIds
     * @param string $status
     */
    private function createAbandonedCart($abandonedModel, $quote, $itemIds, $status)
    {
        $abandonedModel->setStoreId($quote->getStoreId())
                       ->setCustomerId($quote->getCustomerId())
                       ->setEmail($quote->getCustomerEmail())
                       ->setQuoteId($quote->getId())
                       ->setQuoteUpdatedAt($quote->getUpdatedAt())
                       ->setAbandonedCartNumber(1)
                       ->setItemsCount($quote->getItemsCount())
                       ->setItemsIds(implode(',', $itemIds))
                       ->setStatus($status)
                       ->save();
    }

    /**
     * @param string $email
     * @param \Magento\Quote\Model\Quote $quote
     * @param string $campaignId
     * @param int $number
     * @param int $websiteId
     */
    private function sendEmailCampaign($email, $quote, $campaignId, $number, $websiteId)
    {
        $storeId = $quote->getStoreId();
        //interval campaign found
        if ($this->isIntervalCampaignFound($email, $storeId) || ! $campaignId) {
            return;
        }
        $customerId = $quote->getCustomerId();
        $message = ($customerId) ? 'Abandoned Cart ' . $number : 'Guest Abandoned Cart ' . $number;
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
     * @param int $storeId
     * @return int
     */
    private function processGuestFirstAbandonedCart($storeId)
    {
        $abandonedNum = 1;

        $fromTime = $this->getSyncFromTime($this->getSendAfterIntervalForGuest($storeId, $abandonedNum));
        $toTime = clone $fromTime;
        $fromTime->sub($this->dateIntervalFactory->create(['interval_spec' => 'PT5M']));

        //format time
        $fromDate   = $fromTime->format('Y-m-d H:i:s');
        $toDate     = $toTime->format('Y-m-d H:i:s');

        $quoteCollection = $this->getStoreQuotes($fromDate, $toDate, true, $storeId);

        if ($quoteCollection->getSize()) {
            $this->helper->log('Guest AC 1 ' . $fromDate . ' - ' . $toDate);
        }

        $guestCampaignId = $this->getLostBasketGuestCampaignId($abandonedNum, $storeId);
        $result = $this->createGuestFirstAbandonedCart($quoteCollection, $storeId, $guestCampaignId);
        $result += $this->processConfirmedGuestAbandonedCart($storeId, $guestCampaignId);

        return $result;
    }

    /**
     * @param $quoteCollection
     * @param $storeId
     * @param $guestCampaignId
     *
     * @return int
     */
    private function createGuestFirstAbandonedCart($quoteCollection, $storeId, $guestCampaignId)
    {
        $result = 0;
        foreach ($quoteCollection as $quote) {
            $websiteId = $this->helper->storeManager->getStore($storeId)->getWebsiteId();
            if (! $this->updateDataFieldAndCreateAc($quote, $websiteId, $storeId)) {
                continue;
            }

            //send campaign; check if still valid to be sent
            if ($this->isLostBasketGuestEnabled(self::GUEST_LOST_BASKET_ONE, $storeId)) {
                $this->sendEmailCampaign(
                    $quote->getCustomerEmail(),
                    $quote,
                    $guestCampaignId,
                    self::GUEST_LOST_BASKET_ONE,
                    $websiteId
                );
            }

            $this->totalGuests++;
            $result = $this->totalGuests;
        }

        return $result;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Abandoned $abandonedModel
     *
     * @return mixed
     */
    private function abandonedCartAlreadyExists($abandonedModel)
    {
        return $abandonedModel->getId();
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Abandoned $abandonedModel
     * @param \Magento\Quote\Model\Quote $quote
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
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    private function shouldDeleteAbandonedCart($quote)
    {
        return !$quote->getIsActive() || (int) $quote->getItemsCount() === 0;
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Abandoned $abandonedModel
     * @throws \Exception
     */
    private function deleteAbandonedCart($abandonedModel)
    {
        $this->abandonedResource->delete($abandonedModel);
    }

    /**
     * @param int $campaignId
     * @param int $storeId
     * @param int $websiteId
     * @param int $number
     * @param bool $guest
     *
     * @return int
     */
    private function processExistingAbandonedCart($campaignId, $storeId, $websiteId, $number, $guest = false)
    {
        $result = 0;
        if ($guest) {
            $interval = $this->getSendAfterIntervalForGuest($storeId, $number);
            $message = 'Guest';
        } else {
            $interval = $this->getInterval($storeId, $number);
            $message = 'Customer';
        }

        $fromTime = $this->getSyncFromTime($interval);
        $toTime = clone $fromTime;
        $fromTime->sub($this->dateIntervalFactory->create(['interval_spec' => 'PT5M']));
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

            $this->cartInsight->send($quote, $storeId);

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
     * @param int $number
     * @param string $from
     * @param string $to
     * @param int $storeId
     * @param bool $guest
     *
     * @return mixed
     */
    private function getAbandonedCartsForStore($number, $from, $to, $storeId, $guest = false)
    {
        $updated = [
            'from' => $from,
            'to'   => $to,
            'date' => true
        ];

        return $this->abandonedCollectionFactory->create()->getAbandonedCartsForStore(
            --$number,
            $storeId,
            $updated,
            self::STATUS_SENT,
            $this->helper->isOnlySubscribersForAC($storeId),
            $guest
        );
    }

    /**
     * @param array $quoteIds
     * @param int $storeId
     * @return mixed
     */
    private function getProcessedQuoteByIds($quoteIds, $storeId)
    {
        $quoteCollection = $this->quoteCollectionFactory->create()
            ->addFieldToFilter('entity_id', ['in' => $quoteIds])
            ->addFieldToFilter('is_active', 1);

        //process rules on collection
        $ruleModel       = $this->rulesFactory->create();
        $quoteCollection = $ruleModel->process(
            $quoteCollection,
            \Dotdigitalgroup\Email\Model\Rules::ABANDONED,
            $this->helper->storeManager->getStore($storeId)->getWebsiteId()
        );

        return $quoteCollection;
    }

    /**
     * Compare items ids.
     *
     * @param array $quoteItemIds
     * @param array $abandonedItemIds
     * @return bool
     */
    private function isItemsIdsSame($quoteItemIds, $abandonedItemIds)
    {
        return $quoteItemIds == $abandonedItemIds;
    }

    /**
     * @param $storeId
     * @param $guestCampaignId
     *
     * @return int
     */
    private function processConfirmedGuestAbandonedCart($storeId, $guestCampaignId)
    {
        $ac1QuoteIdsWithConfirmedContacts = $this->abandonedCollectionFactory->create()
            ->getCollectionByConfirmedStatus($storeId, true)
            ->getColumnValues('quote_id');

        $quoteCollectionFromIds = $this->orderCollection->create()
            ->getStoreQuotesFromQuoteIds($ac1QuoteIdsWithConfirmedContacts, $storeId);
        return $this->createGuestFirstAbandonedCart(
            $quoteCollectionFromIds,
            $storeId,
            $guestCampaignId
        );
    }

    /**
     * @param $storeId
     * @param $campaignId
     *
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function processConfirmedCustomerAbandonedCart($storeId, $campaignId)
    {
        $ac1QuoteIdsWithConfirmedContacts = $this->abandonedCollectionFactory->create()
            ->getCollectionByConfirmedStatus($storeId)
            ->getColumnValues('quote_id');

        $quoteCollectionFromIds = $this->orderCollection->create()
            ->getStoreQuotesFromQuoteIds($ac1QuoteIdsWithConfirmedContacts, $storeId);
        return $this->createCustomerFirstAbandonedCart(
            $quoteCollectionFromIds,
            $storeId,
            $campaignId
        );
    }

    private function isNotAConfirmedContact($abandonedModel)
    {
        return $abandonedModel->getStatus() !== self::STATUS_CONFIRMED;
    }
}
