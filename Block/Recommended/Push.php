<?php

class Dotdigitalgroup_Email_Block_Recommended_Push extends Dotdigitalgroup_Email_Block_Edc
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
     * get the products to display for table
     */
    public function getLoadedProductCollection()
    {
        $productsToDisplay = array();
        $mode  = $this->getRequest()->getActionName();
        $limit = Mage::helper('ddg/recommended')->getDisplayLimitByMode($mode);

        $productIds = Mage::helper('ddg/recommended')->getProductPushIds();

        $productCollection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToFilter('entity_id', array('in' => $productIds))
            ->setPageSize($limit)
        ;
        foreach ($productCollection as $_product) {
            $productId = $_product->getId();
            $product = Mage::getModel('catalog/product')->load($productId);
            if($product->isSaleable())
                $productsToDisplay[] = $product;

        }

        return $productsToDisplay;

    }

	/**
	 * Display  type mode.
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