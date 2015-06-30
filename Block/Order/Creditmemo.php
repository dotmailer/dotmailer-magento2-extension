<?php
class Dotdigitalgroup_Email_Block_Order_Creditmemo  extends Mage_Sales_Block_Order_Creditmemo_Items
{
    /**
	 * Prepare layout.
	 *
	 * @return Mage_Core_Block_Abstract|void
	 */
    protected function _prepareLayout()
    {
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
    }

	/**
	 * Get current Order.
	 * @return Mage_Sales_Model_Order
	 * @throws Exception
	 */
    public function getOrder()
    {
        $orderId = Mage::registry('order_id');
        $order = Mage::registry('current_order');
        if (! $orderId) {
            $orderId = Mage::app()->getRequest()->getParam('order_id');
            Mage::register('order_id', $orderId);
        }
        if (! $order) {
            $order = Mage::getModel('sales/order')->load($orderId);
            Mage::register('current_order', $order);
        }
        if (! $order->hasCreditmemos()) {
            //throw new Exception('TE - no creditmemo for order : '. $orderId);
            Mage::helper('ddg')->log('TE - no creditmemo for order : '. $orderId);
            return false;
        }

        return $order;
    }

    /**
	 * Order items.
	 * @return mixed
	 */
    public function getCreditmemoItems()
    {
        return Mage::registry('current_order')->getItemsCollection();
    }
}
