<?php

class Dotdigitalgroup_Email_Block_Order extends Dotdigitalgroup_Email_Block_Edc
{

    /**
	 * Prepare layout, set template and title.
	 *
	 * @return Mage_Core_Block_Abstract|void
	 */
    protected function _prepareLayout()
    {
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle($this->__('Order # %s', $this->getOrder()->getRealOrderId()));
        }
    }

    /**
	 * Current Order.
	 *
	 * @return Mage_Core_Model_Abstract|mixed
	 */
    public function getOrder()
    {
        $orderId = Mage::registry('order_id');
        $order = Mage::registry('current_order');
        if (! $orderId) {
            $orderId = Mage::app()->getRequest()->getParam('order_id');
            if(!$orderId)
                return false;
            Mage::unregister('order_id'); // additional measure
            Mage::register('order_id', $orderId);
        }
        if (! $order) {
            if(!$orderId)
                return false;
            $order = Mage::getModel('sales/order')->load($orderId);
            Mage::unregister('current_order'); // additional measure
            Mage::register('current_order', $order);
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

        if(!Mage::helper('ddg/review')->isNewProductOnly($websiteId))
            return $items;

        $customerId = $order->getCustomerId();

        foreach($items as $key => $item)
        {
            $productId = $item->getProduct()->getId();

            $collection = Mage::getModel('review/review')->getCollection();
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