<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Order;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Newsletter\SubscriberFilterer;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Psr\Log\LoggerInterface;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var \Dotdigitalgroup\Email\Model\Newsletter\SubscriberFilterer
     */
    private $subscriberFilterer;

    /**
     * @var string
     */
    protected $_idFieldName = 'email_order_id';

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollection;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollection;

    /**
     * @var Data
     */
    private $helper;

    /**
     * Initialize resource collection.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Order::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Order::class
        );
    }

    /**
     * Collection constructor.
     *
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param QuoteCollectionFactory $quoteCollection
     * @param OrderCollectionFactory $orderCollection
     * @param Data $helper
     * @param SubscriberFilterer $subscriberFilterer
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        QuoteCollectionFactory $quoteCollection,
        OrderCollectionFactory $orderCollection,
        Data $helper,
        SubscriberFilterer $subscriberFilterer,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->helper             = $helper;
        $this->quoteCollection    = $quoteCollection;
        $this->orderCollection    = $orderCollection;
        $this->subscriberFilterer  = $subscriberFilterer;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    /**
     * Load the email order by quote id.
     *
     * @param int $orderId
     * @param int $quoteId
     *
     * @return boolean|\Dotdigitalgroup\Email\Model\Order
     */
    public function loadByOrderIdAndQuoteId($orderId, $quoteId)
    {
        $collection = $this->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('quote_id', $quoteId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Return order collection filtered by order ids.
     *
     * @param array $orderIds
     *
     * @return $this
     */
    public function getOrdersFromIds($orderIds)
    {
        return $this->addFieldToFilter('order_id', ['in' => $orderIds]);
    }

    /**
     * Fetch unprocessed orders.
     *
     * @param string $limit
     * @param array $storeIds
     *
     * @return array
     */
    public function getOrdersToProcess($limit, $storeIds)
    {
        $connectorCollection = $this;
        $connectorCollection->addFieldToFilter('processed', '0');
        $connectorCollection->addFieldToFilter('store_id', ['in' => $storeIds]);
        $connectorCollection->getSelect()->limit($limit);
        $connectorCollection->setOrder(
            'order_id',
            'asc'
        );

        //check number of orders
        if ($connectorCollection->getSize()) {
            return $connectorCollection->getColumnValues('order_id');
        }

        return [];
    }

    /**
     * Get sales collection for review.
     *
     * @param string $orderStatusFromConfig
     * @param array $created
     * @param \Magento\Store\Model\Website $website
     * @param array $campaignOrderIds
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getSalesCollectionForReviews(
        $orderStatusFromConfig,
        $created,
        $website,
        $campaignOrderIds = []
    ) {
        $storeIds = $website->getStoreIds();
        $collection = $this->orderCollection->create()
            ->addFieldToFilter(
                'main_table.status',
                $orderStatusFromConfig
            )
            ->addFieldToFilter('main_table.created_at', $created)
            ->addFieldToFilter(
                'main_table.store_id',
                ['in' => $storeIds]
            );

        if (!empty($campaignOrderIds)) {
            $collection->addFieldToFilter(
                'main_table.increment_id',
                ['nin' => $campaignOrderIds]
            );
        }

        if ($this->helper->isOnlySubscribersForReview($website->getWebsiteId())) {
            $collection = $this->subscriberFilterer->filterBySubscribedStatus($collection);
        }

        return $collection;
    }

    /**
     * Get store quotes for either guests or customers, excluding inactive and empty.
     *
     * @param int $storeId
     * @param array $updated
     * @param bool $guest
     *
     * @return \Magento\Quote\Model\ResourceModel\Quote\Collection
     */
    public function getStoreQuotes($storeId, $updated, $guest = false)
    {
        $salesCollection = $this->getStoreQuotesForGuestsAndCustomers($storeId, $updated);

        if ($guest) {
            $salesCollection->addFieldToFilter('main_table.customer_id', ['null' => true]);
        } else {
            $salesCollection->addFieldToFilter('main_table.customer_id', ['notnull' => true]);
        }

        return $salesCollection;
    }

    /**
     * Get store quotes for both guests and customers, excluding inactive and empty.
     *
     * @param int $storeId
     * @param array $updated
     *
     * @return \Magento\Quote\Model\ResourceModel\Quote\Collection
     */
    public function getStoreQuotesForGuestsAndCustomers($storeId, $updated)
    {
        $salesCollection = $this->quoteCollection->create();
        $salesCollection->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('items_count', ['gt' => 0])
            ->addFieldToFilter('customer_email', ['neq' => ''])
            ->addFieldToFilter('main_table.store_id', $storeId)
            ->addFieldToFilter('main_table.updated_at', $updated);

        if ($this->helper->isOnlySubscribersForAC($storeId)) {
            $salesCollection = $this->subscriberFilterer->filterBySubscribedStatus($salesCollection);
        }

        return $salesCollection;
    }

    /**
     * Get store quotes for both guests and customers, excluding inactive and empty.
     *
     * @param int $storeId
     * @param array $updated
     *
     * @return \Magento\Quote\Model\ResourceModel\Quote\Collection
     */
    public function getStoreQuotesForAutomationEnrollmentGuestsAndCustomers($storeId, $updated)
    {
        $salesCollection = $this->quoteCollection->create();
        $salesCollection->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('customer_email', ['neq' => ''])
            ->addFieldToFilter('main_table.store_id', $storeId)
            ->addFieldToFilter('main_table.updated_at', $updated);

        if ($this->helper->isOnlySubscribersForAC($storeId)) {
            $salesCollection = $this->subscriberFilterer->filterBySubscribedStatus($salesCollection);
        }

        return $salesCollection;
    }

    /**
     * Fetch quotes filtered by quote ids.
     *
     * @param array $quoteIds
     * @param int $storeId
     *
     * @return \Magento\Quote\Model\ResourceModel\Quote\Collection|Object
     */
    public function getStoreQuotesFromQuoteIds($quoteIds, $storeId)
    {
        $salesCollection = $this->quoteCollection->create()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('items_count', ['gt' => 0])
            ->addFieldToFilter('customer_email', ['neq' => ''])
            ->addFieldToFilter('entity_id', ['in' => $quoteIds]);

        if ($this->helper->isOnlySubscribersForAC($storeId)) {
            $salesCollection = $this->subscriberFilterer->filterBySubscribedStatus($salesCollection);
        }

        return $salesCollection;
    }

    /**
     * Utility method to return all the order ids in a collection.
     *
     * @return array
     */
    public function getAllOrderIds(): array
    {
        $ids = [];
        foreach ($this->getItems() as $item) {
            $ids[] = $item->getOrderId();
        }
        return $ids;
    }

    /**
     * Returns order ids filtered by date.
     *
     * @param int $storeId
     * @param \DateTime $time
     *
     * @return array
     */
    public function getOrderIdsFromRecentUnprocessedOrdersSince($storeId, $time)
    {
        return $this->addFieldToFilter('processed', '0')
            ->addFieldToFilter('store_id', $storeId)
            ->addFieldToFilter('updated_at', ['gt' => $time])
            ->getColumnValues('order_id');
    }

    /**
     * Get order ids from increment ids.
     *
     * @param array $incrementIds
     * @return array
     */
    public function getOrderIdsFromIncrementIds(array $incrementIds): array
    {
        return $this->orderCollection->create()
            ->addFieldToFilter(
                'main_table.increment_id',
                ['in' => $incrementIds]
            )
            ->getColumnValues('entity_id');
    }
}
