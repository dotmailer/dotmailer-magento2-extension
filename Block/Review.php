<?php

namespace Dotdigitalgroup\Email\Block;

class Review extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    public $priceHelper;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    public $orderFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory
     */
    public $reviewFactory;

    /**
     * Order constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory $reviewFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\ReviewFactory $reviewFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
        $this->reviewFactory     = $reviewFactory;
        $this->orderFactory      = $orderFactory;
        $this->helper            = $helper;
        $this->priceHelper       = $priceHelper;

        parent::__construct($context, $data);
    }

    /**
     * Current Order.
     *
     * @return bool|mixed
     */
    public function getOrder()
    {
        $params = $this->getRequest()->getParams();
        if (! isset($params['code']) || ! $this->helper->isCodeValid($params['code'])) {
            $this->helper->log('Review no valid code is set');
            return false;
        }

        $orderId = $this->_coreRegistry->registry('order_id');
        $order = $this->_coreRegistry->registry('current_order');
        if (! $orderId) {
            $orderId = (int) $this->getRequest()->getParam('order_id');
            if (! $orderId) {
                return false;
            }
            $this->_coreRegistry->unregister('order_id'); // additional measure
            $this->_coreRegistry->register('order_id', $orderId);
        }
        if (! $order) {
            if (! $orderId) {
                return false;
            }
            $order = $this->orderFactory->create();
            $order = $order->getResource()->load($order, $orderId);
            $this->_coreRegistry->unregister('current_order'); // additional measure
            $this->_coreRegistry->register('current_order', $order);
        }

        return $order;
    }

    /**
     * @param string $mode
     *
     * @return mixed|string
     */
    public function getMode($mode = 'list')
    {
        if ($this->getOrder()) {
            $website = $this->_storeManager
                ->getStore($this->getOrder()->getStoreId())
                ->getWebsite();
            $mode = $this->helper->getReviewDisplayType($website);
        }

        return $mode;
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
        $order = $this->getOrder();

        if (empty($items) || ! $order) {
            return false;
        }

        //if customer is guest then no need to filter any items
        if ($order->getCustomerIsGuest()) {
            return $items;
        }

        if (!$this->helper->isNewProductOnly($websiteId)) {
            return $items;
        }

        $customerId = $order->getCustomerId();

        $items = $this->reviewFactory->create()
            ->filterItemsForReview($items, $customerId, $order);

        return $items;
    }

    /**
     * @return array|\Magento\Framework\Data\Collection\AbstractDb
     */
    public function getItems()
    {
        $order = $this->getOrder();
        if (! $order) {
            return [];
        }

        $items = $this->reviewFactory->create()
            ->getProductCollection($order);

        return $items;
    }

    /**
     * @param $productId
     *
     * @return string
     */
    public function getReviewItemUrl($productId)
    {
        return $this->_urlBuilder->getUrl('review/product/list', ['id' => $productId]);
    }
}
