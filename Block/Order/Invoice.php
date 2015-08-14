<?php
class Dotdigitalgroup_Email_Block_Order_Invoice extends Mage_Sales_Block_Order_Invoice_Items
{
    /**
	 * Prepare layout.
	 * @return Mage_Core_Block_Abstract|void
	 */
    protected function _prepareLayout()
    {
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
    }

	/**
	 * Get current order.
	 * @return Mage_Sales_Model_Order
	 * @throws Exception
	 */
    public function getOrder()
    {
        $orderId = Mage::registry('order_id');
        $order = Mage::registry('current_order');
        if (! $orderId) {
            $orderId = Mage::app()->getRequest()->getParam('order_id');
            if(!$orderId)
                return false;
            Mage::register('order_id', $orderId);
        }
        if (! $order) {
            if(!$orderId)
                return false;
            $order = Mage::getModel('sales/order')->load($orderId);
            Mage::register('current_order', $order);
        }
        if (! $order->hasInvoices()) {
            //throw new Exception('TE - no invoice for order : '. $orderId);
            Mage::helper('ddg')->log('TE - no invoice for order : '. $orderId);
            return false;
        }

        return $order;
    }

}
