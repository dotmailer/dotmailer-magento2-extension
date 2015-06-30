<?php

class Dotdigitalgroup_Email_Block_Recommended_Quoteproducts extends Dotdigitalgroup_Email_Block_Edc
{
    /**
     * Prepare layout, set the template.
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
        //products to be diplayd for recommended pages
        $productsToDisplay = array();
        $quoteId = $this->getRequest()->getParam('quote_id');
        //display mode based on the action name
        $mode  = $this->getRequest()->getActionName();
        $quoteModel = Mage::getModel('sales/quote')->load($quoteId);
        //number of product items to be displayed
        $limit      = Mage::helper('ddg/recommended')->getDisplayLimitByMode($mode);
        $quoteItems = $quoteModel->getAllItems();
        $numItems = count($quoteItems);

        //no product found to display
        if ($numItems == 0 || ! $limit) {
            return array();
        }elseif (count($quoteItems) > $limit) {
            $maxPerChild = 1;
        } else {
            $maxPerChild = number_format($limit / count($quoteItems));
        }

        Mage::helper('ddg')->log('DYNAMIC QUOTE PRODUCTS : limit ' . $limit . ' products : ' . $numItems . ', max per child : '. $maxPerChild);

        foreach ($quoteItems as $item) {
            $i = 0;
            $productId = $item->getProductId();
            //parent product
            $productModel = Mage::getModel('catalog/product')->load($productId);
            //check for product exists
            if ($productModel->getId()) {
                //get single product for current mode
                $recommendedProducts = $this->_getRecommendedProduct($productModel, $mode);
                foreach ($recommendedProducts as $product) {
                    //load child product
                    $product = Mage::getModel('catalog/product')->load($product->getId());
                    //check if still exists
                    if ($product->getId() && count($productsToDisplay) < $limit && $i <= $maxPerChild && $product->isSaleable() && !$product->getParentId()) {
                        //we have a product to display
                        $productsToDisplay[$product->getId()] = $product;
                        $i++;
                    }
                }
            }
            //have reached the limit don't loop for more
            if (count($productsToDisplay) == $limit) {
                break;
            }
        }

        //check for more space to fill up the table with fallback products
        if (count($productsToDisplay) < $limit) {
            $fallbackIds = Mage::helper('ddg/recommended')->getFallbackIds();

            foreach ($fallbackIds as $productId) {
                $product = Mage::getModel('catalog/product')->load($productId);
                if($product->isSaleable())
                    $productsToDisplay[$product->getId()] = $product;
                //stop the limit was reached
                if (count($productsToDisplay) == $limit) {
                    break;
                }
            }
        }

        Mage::helper('ddg')->log('quote - loaded product to display ' . count($productsToDisplay));
        return $productsToDisplay;
    }

    /**
     * Product related items.
     *
     * @param Mage_Catalog_Model_Product $productModel
     * @param $mode
     *
     * @return array
     */
    private  function _getRecommendedProduct(Mage_Catalog_Model_Product $productModel, $mode)
    {
        //array of products to display
        $products = array();
        switch($mode){
            case 'related':
                $products = $productModel->getRelatedProducts();
                break;
            case 'upsell':
                $products = $productModel->getUpSellProducts();
                break;
            case 'crosssell':
                $products = $productModel->getCrossSellProducts();
                break;

        }

        return $products;
    }

    /**
     * Diplay mode type.
     *
     * @return mixed|string
     */
    public function getMode()
    {
        return Mage::helper('ddg/recommended')->getDisplayType();

    }

    /**
     * Number of the colums.
     * @return int|mixed
     * @throws Exception
     */
    public function getColumnCount()
    {
        return Mage::helper('ddg/recommended')->getDisplayLimitByMode($this->getRequest()->getActionName());
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