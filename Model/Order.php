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
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $salesCollection
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
        $item = $this->getCollection()
            ->loadByOrderIdAndQuoteId($orderId, $quoteId);

        if ($item) {
            return $item;
        } else {
            return $this->setOrderId($orderId)
                ->setQuoteId($quoteId);
        }
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
        $item = $this->getCollection()
            ->getEmailOrderRow($orderId, $quoteId, $storeId);

        if ($item) {
            return $item;
        } else {
            return $this->setOrderId($orderId)
                ->setQuoteId($quoteId)
                ->setStoreId($storeId)
                ->setCreatedAt(time());
        }
    }

    /**
     * Get pending orders for import.
     *
     * @param $storeIds
     * @param $limit
     * @param $orderStatuses
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection|\Magento\Framework\DataObject
     */
    public function getOrdersToImport($storeIds, $limit, $orderStatuses)
    {
        return $this->getCollection()
            ->getOrdersToImport($storeIds, $limit, $orderStatuses);
    }

    /**
     * Get pending modified orders to import.
     *
     * @param $storeIds
     * @param $limit
     * @param $orderStatuses
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection
     */
    public function getModifiedOrdersToImport($storeIds, $limit, $orderStatuses)
    {
        return $this->getCollection()
            ->getModifiedOrdersToImport($storeIds, $limit, $orderStatuses);
    }

    /**
     * Get all sent orders
     *
     * @param array $storeIds
     * @param int $limit
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Order\Collection
     */
    public function getAllSentOrders($storeIds, $limit)
    {
        return $this->getCollection()
            ->getAllSentOrders($storeIds, $limit);
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
