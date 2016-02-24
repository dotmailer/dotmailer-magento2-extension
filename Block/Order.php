<?php

namespace Dotdigitalgroup\Email\Block;

class Order extends \Magento\Catalog\Block\Product\AbstractProduct
{

    protected $_quote;
    public $helper;
    public $storeManager;
    public $priceHelper;
    protected $_orderFactory;
    protected $_reviewFactory;
    protected $_reviewHelper;

    /**
     * Order constructor.
     *
     * @param \Magento\Review\Model\ReviewFactory    $reviewFactory
     * @param \Magento\Sales\Model\OrderFactory      $orderFactory
     * @param \Dotdigitalgroup\Email\Helper\Data     $helper
     * @param \Dotdigitalgroup\Email\Helper\Review   $reviewHelper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param array                                  $data
     */
    public function __construct(
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\Review $reviewHelper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {

        $this->_reviewHelper  = $reviewHelper;
        $this->_reviewFactory = $reviewFactory;
        $this->_orderFactory  = $orderFactory;
        $this->helper         = $helper;
        $this->storeManager   = $this->_storeManager;
        $this->priceHelper    = $priceHelper;

        parent::__construct($context, $data);
    }

    /**
     * Current Order.
     */
    public function getOrder()
    {
        $orderId = $this->_coreRegistry->registry('order_id');
        $order   = $this->_coreRegistry->registry('current_order');
        if ( ! $orderId) {
            $orderId = $this->getRequest()->getParam('order_id');
            if ( ! $orderId) {
                return false;
            }
            $this->_coreRegistry->unregister('order_id'); // additional measure
            $this->_coreRegistry->register('order_id', $orderId);
        }
        if ( ! $order) {
            if ( ! $orderId) {
                return false;
            }
            $order = $this->_orderFactory->create()->load($orderId);
            $this->_coreRegistry->unregister('current_order'); // additional measure
            $this->_coreRegistry->register('current_order', $order);
        }

        return $order;
    }

    /**
     * Filter items for review. If a customer has already placed a review for a product then exclude the product.
     *
     * @param array $items
     * @param int   $websiteId
     *
     * @return mixed
     */
    public function filterItemsForReview($items, $websiteId)
    {
        if ( ! count($items)) {
            return false;
        }

        $order = $this->getOrder();

        //if customer is guest then no need to filter any items
        if ($order->getCustomerIsGuest()) {
            return $items;
        }

        if ( ! $this->_reviewHelper->isNewProductOnly($websiteId)) {
            return $items;
        }

        $customerId = $order->getCustomerId();

        foreach ($items as $key => $item) {
            $productId = $item->getProduct()->getId();

            $collection = $this->_reviewFactory->create()->getCollection()
                ->addCustomerFilter($customerId)
                ->addStoreFilter($order->getStoreId())
                ->addFieldToFilter('main_table.entity_pk_value', $productId);

            //remove item if customer has already placed review on this item
            if ($collection->getSize()) {
                unset($items[$key]);
            }
        }

        return $items;
    }
}