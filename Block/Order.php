<?php

namespace Dotdigitalgroup\Email\Block;

class Order  extends \Magento\Framework\View\Element\Template
{

	protected $_quote;
	public $helper;
	public $registry;
	public $storeManager;
	public $priceHelper;
	public $scopeManager;
	public $reviewHelper;
	public $objectManager;


	public function __construct(
		\Magento\Framework\Registry $registry,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Dotdigitalgroup\Email\Helper\Review $reviewHelper,
		\Magento\Framework\Pricing\Helper\Data $priceHelper,
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	)
	{
		parent::__construct( $context, $data );
		$this->helper = $helper;
		$this->reviewHelper = $reviewHelper;
		$this->registry = $registry;
		$this->storeManager = $this->_storeManager;
		$this->priceHelper = $priceHelper;
		$this->scopeManager = $scopeConfig;
		$this->objectManager = $objectManagerInterface;
	}

    /**
	 * Current Order.
	 */
    public function getOrder()
    {
        $orderId = $this->registry->registry('order_id');
        $order = $this->registry->registry('current_order');
        if (! $orderId) {
            $orderId = $this->getRequest()->getParam('order_id');
            if(!$orderId)
                return false;
            $this->registry->unregister('order_id'); // additional measure
            $this->registry->register('order_id', $orderId);
        }
        if (! $order) {
            if(!$orderId)
                return false;
            $order = $this->objectManager->create('Magento\Sales\Model\Order')->load($orderId);
            $this->registry->unregister('current_order'); // additional measure
            $this->registry->register('current_order', $order);
        }

        return $order;
    }

    /**
     * Filter items for review. If a customer has already placed a review for a product then exclude the product.
     *
     * @param array $items
     * @param int $websiteId
     * @return mixed
     */
    public function filterItemsForReview($items, $websiteId)
    {
        if (!count($items))
            return;

        $order = $this->getOrder();

        //if customer is guest then no need to filter any items
        if($order->getCustomerIsGuest())
            return $items;

        if(! $this->objectManager->create('Dotdigitalgroup\Email\Helper\Review')->isNewProductOnly($websiteId))
            return $items;

        $customerId = $order->getCustomerId();

        foreach($items as $key => $item)
        {
            $productId = $item->getProduct()->getId();

            $collection = $this->objectManager->create('Magento\Review\Model\Review')->getCollection();
            $collection->addCustomerFilter($customerId)
                ->addStoreFilter($order->getStoreId())
                ->addFieldToFilter('main_table.entity_pk_value', $productId);

            //remove item if customer has already placed review on this item
            if($collection->getSize())
                unset($items[$key]);
        }

        return $items;
    }
}