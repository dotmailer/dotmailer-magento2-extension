<?php

class Dotdigitalgroup_Email_Block_Products extends Mage_Core_Block_Template
{
    /**
	 * Prepare layout, set template.
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
     * get the products to display for table
     */
    public function getRecommendedProducts()
    {
        $productsToDisplay = array();
        $orderId = $this->getRequest()->getParam('order', false);
        $mode  = $this->getRequest()->getParam('mode', false);
        if ($orderId && $mode) {
            $orderModel = Mage::getModel('sales/order')->load($orderId);
            if ($orderModel->getId()) {
	            $storeId = $orderModel->getStoreId();
	            $appEmulation = Mage::getSingleton('core/app_emulation');
	            $appEmulation->startEnvironmentEmulation($storeId);
                //order products
                $productRecommended = Mage::getModel('ddg_automation/dynamic_recommended', $orderModel);
                $productRecommended->setMode($mode);

                //get the order items recommendations
                $productsToDisplay = $productRecommended->getProducts();
            }
        }

        return $productsToDisplay;
    }


    /**
	 * Price html block.
	 *
	 * @param $product
	 *
	 * @return string
	 */
    public function getPriceHtml($product)
    {
        $this->setTemplate('connector/price.phtml');
        $this->setProduct($product);
        return $this->toHtml();
    }

    /**
	 * Display type mode.
	 * @return mixed|string
	 */
    public function getDisplayType()
    {
        return Mage::helper('ddg/recommended')->getDisplayType();

    }
}