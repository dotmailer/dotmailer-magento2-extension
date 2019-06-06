<?php

namespace Dotdigitalgroup\Email\Block;

/**
 * Review block
 *
 * @api
 */
class Review extends Recommended
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
     * @var \Magento\Sales\Api\Data\OrderInterfaceFactory
     */
    public $orderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Review
     */
    public $review;

    /**
     * @var \Magento\Sales\Model\Spi\OrderResourceInterface
     */
    private $orderResource;

    /**
     * Review constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param Helper\Font $font
     * @param \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder
     * @param \Magento\Sales\Model\Spi\OrderResourceInterface $orderResource
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Review $review
     * @param \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        Helper\Font $font,
        \Dotdigitalgroup\Email\Model\Catalog\UrlFinder $urlFinder,
        \Magento\Sales\Model\Spi\OrderResourceInterface $orderResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Review $review,
        \Magento\Sales\Api\Data\OrderInterfaceFactory $orderFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        array $data = []
    ) {
        $this->review     = $review;
        $this->orderFactory      = $orderFactory;
        $this->helper            = $helper;
        $this->priceHelper       = $priceHelper;
        $this->orderResource     = $orderResource;

        parent::__construct($context, $font, $urlFinder, $data);
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
            $this->orderResource->load($order, $orderId);
            $this->_coreRegistry->unregister('current_order'); // additional measure
            $this->_coreRegistry->register('current_order', $order);
        }

        return $order;
    }

    /**
     * @param string $mode
     *
     * @return boolean|string
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
     * @return boolean|array
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

        $items = $this->review->filterItemsForReview($items, $customerId, $order);

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

        $items = $this->review->getProductCollection($order);

        return $items;
    }

    /**
     * @param int|string $productId
     *
     * @return string
     */
    public function getReviewItemUrl($productId)
    {
        return $this->_urlBuilder->getUrl('review/product/list', ['id' => $productId]);
    }
}
