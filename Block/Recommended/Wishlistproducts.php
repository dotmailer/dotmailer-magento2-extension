<?php

class Dotdigitalgroup_Email_Block_Recommended_Wishlistproducts extends Dotdigitalgroup_Email_Block_Edc
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

    protected function _getWishlistItems()
    {
        $wishlist = $this->_getWishlist();
        if($wishlist && count($wishlist->getItemCollection()))
            return $wishlist->getItemCollection();
        else
            return array();
    }

    protected function _getWishlist()
    {
        $customerId = Mage::app()->getRequest()->getParam('customer_id');
        if(!$customerId)
            return array();

        $customer = Mage::getModel('customer/customer')->load($customerId);
        if(!$customer->getId())
            return array();

        $collection = Mage::getModel('wishlist/wishlist')->getCollection();
        $collection->addFieldToFilter('customer_id', $customerId)
            ->setOrder('updated_at', 'DESC');

        if ($collection->count())
            return $collection->getFirstItem();
        else
            return array();

    }

    /**
     * get the products to display for table
     */
    public function getLoadedProductCollection()
    {
        //products to be display for recommended pages
        $productsToDisplay = array();
        //display mode based on the action name
        $mode  = $this->getRequest()->getActionName();
        //number of product items to be displayed
        $limit = Mage::helper('ddg/recommended')->getDisplayLimitByMode($mode);
        $items = $this->_getWishlistItems();
        $numItems = count($items);

        //no product found to display
        if ($numItems == 0 || ! $limit) {
            return array();
        }elseif (count($items) > $limit) {
            $maxPerChild = 1;
        } else {
            $maxPerChild = number_format($limit / count($items));
        }

        Mage::helper('ddg')->log('DYNAMIC WISHLIST PRODUCTS : limit ' . $limit . ' products : ' . $numItems . ', max per child : '. $maxPerChild);

        foreach ($items as $item) {
            $i = 0;
            //parent product
            $product = $item->getProduct();
            //check for product exists
            if ($product->getId()) {
                //get single product for current mode
                $recommendedProducts = $this->_getRecommendedProduct($product, $mode);
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

        Mage::helper('ddg')->log('wishlist - loaded product to display ' . count($productsToDisplay));
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
        $this->setTemplate('ddg/product/price.phtml');
        $this->setProduct($product);
        return $this->toHtml();
    }
}