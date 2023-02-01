<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\ResourceModel\Order as OrderResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class Order extends \Magento\Framework\Model\AbstractModel
{
    public const EMAIL_ORDER_NOT_IMPORTED = 0;

    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * Constructor.
     *
     * @return void
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
     * @param OrderResource $orderResource
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param array $data
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        OrderResource $orderResource,
        OrderCollectionFactory $orderCollectionFactory,
        array $data = [],
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null
    ) {
        $this->orderResource = $orderResource;
        $this->orderCollectionFactory = $orderCollectionFactory;
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
    public function loadOrCreateOrder($orderId, $quoteId)
    {
        $item = $this->orderCollectionFactory
            ->create()
            ->loadByOrderIdAndQuoteId($orderId, $quoteId);

        if ($item) {
            return $item;
        } else {
            return $this->setOrderId($orderId)
                ->setQuoteId($quoteId);
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
