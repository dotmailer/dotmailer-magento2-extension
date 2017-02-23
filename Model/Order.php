<?php

namespace Dotdigitalgroup\Email\Model;

class Order extends \Magento\Framework\Model\AbstractModel
{
    const EMAIL_ORDER_NOT_IMPORTED = null;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public $salesCollection;
    /**
     * @var ResourceModel\Order\Collection
     */
    public $emailOrderCollection;

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Dotdigitalgroup\Email\Model\ResourceModel\Order');
    }

    /**
     * Order constructor.
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $salesCollection
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesCollection,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
    
        $this->salesCollection = $salesCollection;

        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Load the email order by quote id.
     *
     * @param int $orderId
     * @param int $quoteId
     *
     * @return $this|\Magento\Framework\DataObject
     */
    public function loadByOrderId($orderId, $quoteId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('quote_id', $quoteId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        } else {
            $this->setOrderId($orderId)
                ->setQuoteId($quoteId);
        }

        return $this;
    }

    /**
     * Get connector order.
     *
     * @param int $orderId
     * @param int $quoteId
     * @param int $storeId
     *
     * @return $this|\Magento\Framework\DataObject
     */
    public function getEmailOrderRow($orderId, $quoteId, $storeId)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('store_id', $storeId);

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        } else {
            $this->setOrderId($orderId)
                ->setQuoteId($quoteId)
                ->setStoreId($storeId)
                ->setCreatedAt(time());
        }

        return $this;
    }

    /**
     * Get pending orders for import.
     *
     * @param $storeIds
     * @param $limit
     * @param $orderStatuses
     * @return
     */
    public function getOrdersToImport($storeIds, $limit, $orderStatuses)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->addFieldToFilter('order_status', ['in' => $orderStatuses])
            ->addFieldToFilter('email_imported', ['null' => true]);

        $collection->getSelect()->limit($limit);

        return $collection;
    }

    /**
     * Get pending modfied orders to import.
     * @param $storeIds
     * @param $limit
     * @param $orderStatuses
     * @return
     */
    public function getModifiedOrdersToImport($storeIds, $limit, $orderStatuses)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->addFieldToFilter('order_status', ['in' => $orderStatuses])
            ->addFieldToFilter('email_imported', '1')
            ->addFieldToFilter('modified', '1');

        $collection->getSelect()->limit($limit);

        return $collection;
    }

    /**
     * Get all sent orders older then certain days.
     *
     * @param array $storeIds
     * @param int $limit
     *
     * @return
     */
    public function getAllSentOrders($storeIds, $limit)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('email_imported', 1)
            ->addFieldToFilter('store_id', ['in' => $storeIds]);

        $collection->getSelect()->limit($limit);

        return $collection->load();
    }

    /**
     * @param $orderIds
     * @return mixed
     */
    public function getSalesOrdersWithIds($orderIds)
    {
        return $this->salesCollection->create()
            ->addFieldToFilter('entity_id', ['in' => $orderIds]);
    }
}
