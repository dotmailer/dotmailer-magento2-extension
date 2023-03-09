<?php

namespace Dotdigitalgroup\Email\Model\Sales;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Abandoned as AbandonedModel;
use Dotdigitalgroup\Email\Model\AbandonedCart\Interval;
use Dotdigitalgroup\Email\Model\AbandonedFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\CollectionFactory as AbandonedCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory as CampaignCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\PendingContact\PendingContactUpdater;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Campaign;
use Dotdigitalgroup\Email\Model\AbandonedCart\TimeLimit;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\AbandonedCartFactory as AbandonedCartUpdaterFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\ResourceModel\Quote\Collection as QuoteCollection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Customer and guest Abandoned Carts.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Quote
{
    //customer
    public const XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_1 = 'abandoned_carts/customers/enabled_1';
    public const XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_2 = 'abandoned_carts/customers/enabled_2';
    public const XML_PATH_LOSTBASKET_CUSTOMER_ENABLED_3 = 'abandoned_carts/customers/enabled_3';
    public const XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_1 = 'abandoned_carts/customers/campaign_1';
    public const XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_2 = 'abandoned_carts/customers/campaign_2';
    public const XML_PATH_LOSTBASKET_CUSTOMER_CAMPAIGN_3 = 'abandoned_carts/customers/campaign_3';

    //guest
    public const XML_PATH_LOSTBASKET_GUEST_ENABLED_1 = 'abandoned_carts/guests/enabled_1';
    public const XML_PATH_LOSTBASKET_GUEST_ENABLED_2 = 'abandoned_carts/guests/enabled_2';
    public const XML_PATH_LOSTBASKET_GUEST_ENABLED_3 = 'abandoned_carts/guests/enabled_3';
    public const XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_1 = 'abandoned_carts/guests/campaign_1';
    public const XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_2 = 'abandoned_carts/guests/campaign_2';
    public const XML_PATH_LOSTBASKET_GUEST_CAMPAIGN_3 = 'abandoned_carts/guests/campaign_3';

    private const CUSTOMER_LOST_BASKET_ONE = 1;
    private const CUSTOMER_LOST_BASKET_TWO = 2;
    private const CUSTOMER_LOST_BASKET_THREE = 3;

    private const GUEST_LOST_BASKET_ONE = 1;
    private const GUEST_LOST_BASKET_TWO = 2;
    private const GUEST_LOST_BASKET_THREE = 3;

    private const STATUS_SENT = 'Sent';

    /**
     * @var Interval
     */
    private $interval;

    /**
     * @var AbandonedFactory
     */
    private $abandonedFactory;

    /**
     * @var AbandonedCollectionFactory
     */
    private $abandonedCollectionFactory;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var Campaign
     */
    private $campaignResource;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
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
     * @var CampaignCollectionFactory
     */
    private $campaignCollectionFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\RulesFactory
     */
    private $rulesFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timeZone;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned
     */
    private $abandonedResource;

    /**
     * @var PendingContactUpdater
     */
    private $pendingContactUpdater;

    /**
     * @var \Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data
     */
    private $cartInsight;

    /**
     * @var TimeLimit
     */
    private $timeLimit;

    /**
     * @var AbandonedCartUpdaterFactory
     */
    private $dataFieldUpdaterFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Total number of customers found.
     * @var int
     */
    private $totalCustomers = 0;

    /**
     * Total number of guest found.
     * @var int
     */
    private $totalGuests = 0;

    /**
     * Quote constructor.
     *
     * @param AbandonedFactory $abandonedFactory
     * @param AbandonedCollectionFactory $abandonedCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory
     * @param Campaign $campaignResource
     * @param \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory
     * @param CampaignCollectionFactory $campaignCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned $abandonedResource
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param PendingContactUpdater $pendingContactUpdater
     * @param \Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data $cartInsight
     * @param TimeLimit $timeLimit
     * @param Logger $logger
     * @param AbandonedCartUpdaterFactory $dataFieldUpdaterFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Interval $interval,
        AbandonedFactory $abandonedFactory,
        AbandonedCollectionFactory $abandonedCollectionFactory,
        \Dotdigitalgroup\Email\Model\RulesFactory $rulesFactory,
        Campaign $campaignResource,
        \Dotdigitalgroup\Email\Model\CampaignFactory $campaignFactory,
        CampaignCollectionFactory $campaignCollectionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned $abandonedResource,
        \Dotdigitalgroup\Email\Helper\Data $data,
        QuoteCollectionFactory $quoteCollectionFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        PendingContactUpdater $pendingContactUpdater,
        \Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data $cartInsight,
        TimeLimit $timeLimit,
        Logger $logger,
        AbandonedCartUpdaterFactory $dataFieldUpdaterFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->interval = $interval;
        $this->timeZone = $timezone;
        $this->rulesFactory = $rulesFactory;
        $this->campaignFactory = $campaignFactory;
        $this->helper = $data;
        $this->abandonedFactory = $abandonedFactory;
        $this->campaignResource = $campaignResource;
        $this->orderCollection = $collectionFactory;
        $this->abandonedResource = $abandonedResource;
        $this->scopeConfig = $scopeConfig;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->campaignCollectionFactory = $campaignCollectionFactory;
        $this->abandonedCollectionFactory = $abandonedCollectionFactory;
        $this->pendingContactUpdater = $pendingContactUpdater;
        $this->cartInsight = $cartInsight;
        $this->timeLimit = $timeLimit;
        $this->logger = $logger;
        $this->dataFieldUpdaterFactory = $dataFieldUpdaterFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Process abandoned carts.
     *
     * @return array
     */
    public function processAbandonedCarts()
    {
        $result = [];
        $stores = $this->storeManager->getStores();
        $this->pendingContactUpdater->update();

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
     * Check if abandoned cart email series is enabled for customers.
     *
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
     * Get quotes.
     *
     * @param array $updated
     * @param bool $guest
     * @param int $storeId
     * @return QuoteCollection|\Magento\Sales\Model\ResourceModel\Order\Collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreQuotes(array $updated, $guest = false, $storeId = 0)
    {
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
     * Get the campaign mapped for this number in the series.
     *
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
     * Check for another campaign sent during a given interval.
     *
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
        $campaignLimit = $this->campaignCollectionFactory->create()
            ->getNumberOfCampaignsForContactByInterval($email, $updated);

        //found campaign
        if ($campaignLimit) {
            return true;
        }

        return false;
    }

    /**
     * Check if abandoned cart email series is enabled for guests.
     *
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
     * Get the campaign mapped for this number in the series.
     *
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
     * Process Abandoned Cart 1 for customers.
     *
     * @param int $storeId
     * @return int|string
     */
    private function processCustomerFirstAbandonedCart($storeId)
    {
        $abandonedNum = 1;
        $updated = $this->interval->getAbandonedCartSeriesCustomerWindow($storeId, $abandonedNum);

        //active quotes
        $quoteCollection = $this->getStoreQuotes($updated, false, $storeId);

        //found abandoned carts
        if ($quoteCollection->getSize()) {
            $this->helper->log('Customer AC 1 ' . $updated['from'] . ' - ' . $updated['to']);
        }

        //campaign id for customers
        $campaignId = $this->getLostBasketCustomerCampaignId($abandonedNum, $storeId);

        $result = $this->createCustomerFirstAbandonedCart($quoteCollection, $storeId, $campaignId);
        $result += $this->processConfirmedCustomerAbandonedCart($storeId, $campaignId);

        return $result;
    }

    /**
     * Create a first abandoned cart row for a customer.
     *
     * @param QuoteCollection $quoteCollection
     * @param string|int $storeId
     * @param string $campaignId
     *
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function createCustomerFirstAbandonedCart($quoteCollection, $storeId, $campaignId)
    {
        $result = 0;
        foreach ($quoteCollection as $quote) {
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
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
     * Send cart insight, create a row, then post data fields.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param int $websiteId
     * @param int $storeId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function updateDataFieldAndCreateAc($quote, $websiteId, $storeId)
    {
        $quoteId = $quote->getId();

        try {
            $items = $quote->getAllItems();
        } catch (\Exception $e) {
            $items = [];
            $this->logger->debug(
                sprintf('Error fetching items for quote ID: %s', $quoteId),
                [(string) $e]
            );
        }

        $email = $quote->getCustomerEmail();
        $itemIds = $this->getQuoteItemIds($items);
        $abandonedModel = $this->abandonedFactory->create()
            ->loadByQuoteId($quoteId);
        $contact = $this->helper->getOrCreateContact($email, $websiteId);
        if (!$contact) {
            return false;
        }

        $this->cartInsight->send($quote, $storeId);

        if ($contact->status === StatusInterface::PENDING_OPT_IN) {
            $this->createAbandonedCart($abandonedModel, $quote, $itemIds, StatusInterface::PENDING_OPT_IN);
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
            $this->createAbandonedCart($abandonedModel, $quote, $itemIds, self::STATUS_SENT);
            $this->dataFieldUpdaterFactory->create()
                ->setDataFields(
                    $email,
                    $websiteId,
                    $quoteId,
                    $this->storeManager->getStore($storeId)->getName(),
                    $this->getMostExpensiveItem($items)
                )
                ->updateDataFields();

            return true;
        }
    }

    /**
     * Get product ids from an array of quote item ids.
     *
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
     * Get the most expensive quote item.
     *
     * @param \Magento\Quote\Model\Quote\Item[] $items
     * @return bool|\Magento\Quote\Model\Quote\Item
     */
    public function getMostExpensiveItem($items)
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
     * Check if the items in a quote match the items_count in an abandoned cart row.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param AbandonedModel $abandonedModel
     * @return bool
     */
    private function isItemsChanged($quote, $abandonedModel)
    {
        if ($quote->getItemsCount() != $abandonedModel->getItemsCount()) {
            return true;
        } else {
            //number of items matches
            try {
                $quoteItems = $quote->getAllItems();
            } catch (\Exception $e) {
                $quoteItems = [];
                $this->logger->debug(
                    sprintf('Error fetching items for quote ID: %s', $quote->getId()),
                    [(string) $e]
                );
            }

            $quoteItemIds = $this->getQuoteItemIds($quoteItems);
            $abandonedItemIds = explode(',', $abandonedModel->getItemsIds());

            //quote items not same
            if (! $this->isItemsIdsSame($quoteItemIds, $abandonedItemIds)) {
                return true;
            }

            return false;
        }
    }

    /**
     * Create an email_abandoned_cart row.
     *
     * @param AbandonedModel $abandonedModel
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
            ->setStatus($status);
        $this->abandonedResource->save($abandonedModel);
    }

    /**
     * Queue a campaign in email_campaign.
     *
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
     * Process Abandoned Cart 1 for guests.
     *
     * @param int $storeId
     * @return int
     */
    private function processGuestFirstAbandonedCart($storeId)
    {
        $abandonedNum = 1;
        $updated = $this->interval->getAbandonedCartSeriesGuestWindow($storeId, $abandonedNum);

        $quoteCollection = $this->getStoreQuotes($updated, true, $storeId);

        if ($quoteCollection->getSize()) {
            $this->helper->log('Guest AC 1 ' . $updated['from'] . ' - ' . $updated['to']);
        }

        $guestCampaignId = $this->getLostBasketGuestCampaignId($abandonedNum, $storeId);
        $result = $this->createGuestFirstAbandonedCart($quoteCollection, $storeId, $guestCampaignId);
        $result += $this->processConfirmedGuestAbandonedCart($storeId, $guestCampaignId);

        return $result;
    }

    /**
     * Create a first abandoned cart row for a guest.
     *
     * @param QuoteCollection $quoteCollection
     * @param string|int $storeId
     * @param string $guestCampaignId
     * @return int
     */
    private function createGuestFirstAbandonedCart($quoteCollection, $storeId, $guestCampaignId)
    {
        $result = 0;
        foreach ($quoteCollection as $quote) {
            $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
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
     * Check if this abandoned cart already exists.
     *
     * @param AbandonedModel $abandonedModel
     *
     * @return mixed
     */
    private function abandonedCartAlreadyExists($abandonedModel)
    {
        return $abandonedModel->getId();
    }

    /**
     * Decide whether to create or update an abandoned cart row.
     *
     * If the quote is no longer active, items count is 0 AND items have not changed = don't send.
     *
     * @param AbandonedModel $abandonedModel
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
     * Decide whether to delete an abandoned cart.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    private function shouldDeleteAbandonedCart($quote)
    {
        return !$quote->getIsActive() || (int) $quote->getItemsCount() === 0;
    }

    /**
     * Delete an abandoned cart.
     *
     * @param AbandonedModel $abandonedModel
     * @throws \Exception
     */
    private function deleteAbandonedCart($abandonedModel)
    {
        $this->abandonedResource->delete($abandonedModel);
    }

    /**
     * Handle actions for abandoned cart series 2 and 3.
     *
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
            $interval = $this->interval->getIntervalForGuestEmailSeries($storeId, $number);
            $message = 'Guest';
        } else {
            $interval = $this->interval->getIntervalForCustomerEmailSeries($storeId, $number);
            $message = 'Customer';
        }

        $updated = $this->interval->getAbandonedCartSeriesWindow($interval);

        //get abandoned carts already sent
        $abandonedCollection = $this->getAbandonedCartsForStore(
            $number,
            $updated,
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
                $message . ' Abandoned Cart ' . $number . ',from ' . $updated['from'] . '  :  ' . $updated['to'] . ', storeId '
                . $storeId
            );
        }

        foreach ($quoteCollection as $quote) {

            $this->cartInsight->send($quote, $storeId);

            $quoteId = $quote->getId();
            $email = $quote->getCustomerEmail();

            try {
                $quoteItems = $quote->getAllItems();
            } catch (\Exception $e) {
                $quoteItems = [];
                $this->logger->debug(
                    sprintf('Error fetching items for quote ID: %s', $quoteId),
                    [(string) $e]
                );
            }

            $this->dataFieldUpdaterFactory->create()
                ->setDataFields(
                    $email,
                    $websiteId,
                    $quoteId,
                    $this->storeManager->getStore($storeId)->getName(),
                    $this->getMostExpensiveItem($quoteItems)
                )->updateDataFields();

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
                ->setQuoteUpdatedAt($quote->getUpdatedAt());
            $this->abandonedResource->save($abandonedModel);

            $this->sendEmailCampaign($email, $quote, $campaignId, $number, $websiteId);
            $result++;
        }

        return $result;
    }

    /**
     * Get abandoned carts by store.
     *
     * @param int $number
     * @param array $updated
     * @param int $storeId
     * @param bool $guest
     *
     * @return mixed
     */
    private function getAbandonedCartsForStore($number, array $updated, $storeId, $guest = false)
    {
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
     * Get quotes by ids, after running exclusion rules.
     *
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
            $this->storeManager->getStore($storeId)->getWebsiteId()
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
     * Process abandoned carts for confirmed guests.
     *
     * @param string|int $storeId
     * @param string $guestCampaignId
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
     * Process abandoned carts for confirmed customers.
     *
     * @param string|int $storeId
     * @param string $campaignId
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

    /**
     * Check abandoned cart status is not 'Confirmed'.
     *
     * @param AbandonedModel $abandonedModel
     *
     * @return bool
     */
    private function isNotAConfirmedContact($abandonedModel)
    {
        return $abandonedModel->getStatus() !== StatusInterface::CONFIRMED;
    }
}
