<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Order;

/**
 * Class Collection
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'email_order_id';

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollection;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    private $quoteCollection;

    /**
     * Initialize resource collection.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Order::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Order::class
        );
    }

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollection
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollection,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollection,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->quoteCollection    = $quoteCollection;
        $this->orderCollection    = $orderCollection;
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
     * @return mixed
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
     * Get connector order.
     *
     * @param int $orderId
     * @param int $quoteId
     * @param int $storeId
     *
     * @return mixed
     */
    public function getEmailOrderRow($orderId, $quoteId, $storeId)
    {
        $collection = $this->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('store_id', $storeId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Get pending orders for import.
     *
     * @param array $storeIds
     * @param int $limit
     * @param array $orderStatuses
     *
     * @return $this
     */
    public function getOrdersToImport($storeIds, $limit, $orderStatuses)
    {
        $collection = $this->addFieldToFilter('store_id', ['in' => $storeIds])
            ->addFieldToFilter('order_status', ['in' => $orderStatuses])
            ->addFieldToFilter('email_imported', ['null' => true]);

        $collection->getSelect()->limit($limit);

        return $collection;
    }

    /**
     * Get pending modified orders to import.
     *
     * @param array $storeIds
     * @param int $limit
     * @param array $orderStatuses
     *
     * @return $this
     */
    public function getModifiedOrdersToImport($storeIds, $limit, $orderStatuses)
    {
        $collection = $this->addFieldToFilter('store_id', ['in' => $storeIds])
            ->addFieldToFilter('order_status', ['in' => $orderStatuses])
            ->addFieldToFilter('email_imported', '1')
            ->addFieldToFilter('modified', '1');

        $collection->getSelect()->limit($limit);

        return $collection;
    }

    /**
     * Get all sent orders.
     *
     * @param array $storeIds
     * @param int $limit
     *
     * @return $this
     */
    public function getAllSentOrders($storeIds, $limit)
    {
        $collection = $this->addFieldToFilter('email_imported', 1)
            ->addFieldToFilter('store_id', ['in' => $storeIds]);

        $collection->getSelect()->limit($limit);

        return $collection->load();
    }

    /**
     * Get sales collection for review.
     *
     * @param string $orderStatusFromConfig
     * @param mixed $created
     * @param array $storeIds
     * @param array $campaignOrderIds
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getSalesCollectionForReviews(
        $orderStatusFromConfig,
        $created,
        $storeIds,
        $campaignOrderIds = []
    ) {
    
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

        return $collection;
    }

    /**
     * Get customer last order id.
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param array $storeIds
     *
     * @return mixed
     */
    public function getCustomerLastOrderId(\Magento\Customer\Model\Customer $customer, $storeIds)
    {
        $collection = $this->orderCollection->create()
            ->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->setPageSize(1)
            ->setOrder('entity_id');

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Get customer last quote id.
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param array $storeIds
     *
     * @return mixed
     */
    public function getCustomerLastQuoteId(\Magento\Customer\Model\Customer $customer, $storeIds)
    {
        $collection = $this->quoteCollection->create()
            ->addFieldToFilter('customer_id', $customer->getId())
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->setPageSize(1)
            ->setOrder('entity_id');

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Get store quotes excluding inactive and empty.
     *
     * @param int $storeId
     * @param mixed $updated
     * @param bool $guest
     *
     * @return \Magento\Quote\Model\ResourceModel\Quote\Collection
     */
    public function getStoreQuotes($storeId, $updated, $guest = false)
    {
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

        return $salesCollection;
    }

    /**
     * Check emails exist in sales order table.
     *
     * @param array $emails
     *
     * @return array
     */
    public function checkInSales($emails)
    {
        $collection = $this->orderCollection->create()
            ->addFieldToFilter('customer_email', ['in' => $emails]);
        return $collection->getColumnValues('customer_email');
    }
}
