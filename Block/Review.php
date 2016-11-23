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
     * @var \Magento\Review\Model\ReviewFactory
     */
    public $reviewFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public $productCollection;

    /**
     * Order constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     * @param \Magento\Review\Model\ReviewFactory $reviewFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection,
        \Magento\Review\Model\ReviewFactory $reviewFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
        $this->productCollection = $productCollection;
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
        $orderId = $this->_coreRegistry->registry('order_id');
        $order = $this->_coreRegistry->registry('current_order');
        if (! $orderId) {
            $orderId = $this->getRequest()->getParam('order_id');
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
            $order = $this->orderFactory->create()->load($orderId);
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

        foreach ($items as $key => $item) {
            $productId = $item->getProduct()->getId();

            $collection = $this->reviewFactory->create()->getCollection()
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

    /**
     * @return array|\Magento\Framework\Data\Collection\AbstractDb
     */
    public function getItems()
    {
        $order = $this->getOrder();
        if (! $order) {
            return [];
        }
        $items = $order->getAllVisibleItems();
        $productIds = [];
        //get the product ids for the collection
        foreach ($items as $item) {
            $productIds[] = $item->getProductId();
        }
        $items = $this->productCollection
            ->addAttributeToSelect('*')
            ->addFieldToFilter('entity_id', ['in' => $productIds]);

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
