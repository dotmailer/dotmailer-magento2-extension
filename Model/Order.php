<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\ResourceModel\Order as OrderResource;

class Order extends \Magento\Framework\Model\AbstractModel
{
    public const EMAIL_ORDER_NOT_IMPORTED = 0;

    /**
     * @var \Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory
     */
    private $salesCollection;

    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * Constructor.
     *
     * @return null
     */
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Dotdigitalgroup\Email\Model\ResourceModel\Order::class);
    }

    /**
     * Order constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory $salesCollection
     * @param OrderResource $orderResource
     * @param array $data
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Api\Data\OrderSearchResultInterfaceFactory $salesCollection,
        OrderResource $orderResource,
        array $data = [],
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null
    ) {

        $this->salesCollection = $salesCollection;
        $this->orderResource = $orderResource;
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
     * Updates orders.
     *
     * @param string|null $from
     * @param string|null $to
     * @return int
     */
    public function reset(string $from = null, string $to = null)
    {
        return $this->orderResource->resetOrders($from, $to);
    }
}
