<?php

class Dotdigitalgroup_Email_Block_Recommended_Recentlyviewed extends Dotdigitalgroup_Email_Block_Edc
{

	/**
	 * Prepare layout, set template.
	 * @return Mage_Core_Block_Abstract|void
	 */
	protected function _prepareLayout()
    {
        if ($root = $this->getLayout()->getBlock('root')) {
            $root->setTemplate('page/blank.phtml');
        }
    }

	/**
	 * Products collection.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getLoadedProductCollection()
    {
        $productsToDisplay = array();
        $mode = $this->getRequest()->getActionName();
        $customerId = $this->getRequest()->getParam('customer_id');
        $limit = Mage::helper('ddg/recommended')->getDisplayLimitByMode($mode);
        //login customer to receive the recent products
        $session = Mage::getSingleton('customer/session');
        $isLoggedIn = $session->loginById($customerId);
        /** @var Mage_Reports_Block_Product_Viewed $collection */
        $collection = Mage::getSingleton('Mage_Reports_Block_Product_Viewed');
        $items = $collection->getItemsCollection()
            ->setPageSize($limit);
        Mage::helper('ddg')->log('Recentlyviewed customer  : ' . $customerId . ', mode ' . $mode . ', limit : ' . $limit .
            ', items found : ' . count($items) . ', is customer logged in : ' . $isLoggedIn . ', products :' . count($productsToDisplay));
        foreach ($items as $product) {
            $product = Mage::getModel('catalog/product')->load($product->getId());
            if($product->isSalable())
                $productsToDisplay[$product->getId()] = $product;

        }
        $session->logout();

        return $productsToDisplay;
    }


	/**
	 * Display mode type.
	 *
	 * @return mixed|string
	 */
	public function getMode()
    {
        return Mage::helper('ddg/recommended')->getDisplayType();

    }

	/**
	 * Price html.
	 * @param $product
	 *
	 * @return string
	 */
	public function getPriceHtml($product)
    {
        $this->setTemplate('connector/product/price.phtml');
        $this->setProduct($product);
        return $this->toHtml();
    }
}