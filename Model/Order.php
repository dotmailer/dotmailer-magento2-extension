<?php

namespace Dotdigitalgroup\Email\Model;

class Order extends \Magento\Framework\Model\AbstractModel
{
    const EMAIL_ORDER_NOT_IMPORTED = null;

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init('Dotdigitalgroup\Email\Model\ResourceModel\Order');
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
     * Get all orders with particular status within certain days.
     *
     * @param array $storeIds
     * @param int $limit
     * @param array $orderStatuses
     * @param bool|false $modified
     *
     * @return $this
     */
    public function getOrdersToImport(
        $storeIds,
        $limit,
        $orderStatuses,
        $modified = false
    ) {
        $collection = $this->getCollection()
            ->addFieldToFilter('store_id', ['in' => $storeIds])
            ->addFieldToFilter('order_status', ['in' => $orderStatuses]);

        if ($modified) {
            $collection
                ->addFieldToFilter('email_imported', 1)
                ->addFieldToFilter('modified', 1);
        } else {
            $collection->addFieldToFilter(
                'email_imported', ['null' => true]
            );
        }

        $collection->getSelect()->limit($limit);

        return $collection;
    }

    /**
     * Get all sent orders older then certain days.
     *
     * @param array $storeIds
     * @param int $limit
     *
     * @return $this
     */
    public function getAllSentOrders($storeIds, $limit)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('email_imported', 1)
            ->addFieldToFilter('store_id', ['in' => $storeIds]);

        $collection->getSelect()->limit($limit);

        return $collection->load();
    }
}
