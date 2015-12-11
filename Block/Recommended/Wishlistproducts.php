<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Wishlistproducts extends \Magento\Catalog\Block\Product\AbstractProduct
{
	public $helper;
	public $priceHelper;
	protected $_localeDate;
	public $scopeManager;
	public $objectManager;


	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\Pricing\Helper\Data $priceHelper,
		\Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Magento\Catalog\Block\Product\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	)
	{
		parent::__construct( $context, $data );
		$this->helper = $helper;
		$this->recommnededHelper = $recommended;
		$this->priceHelper = $priceHelper;
		$this->_localeDate = $localeDate;
		$this->scopeManager = $scopeConfig;
		$this->storeManager = $this->_storeManager;
		$this->objectManager = $objectManagerInterface;
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
        $customerId = $this->getRequest()->getParam('customer_id');
        if(!$customerId)
            return array();

        $customer = $this->objectManager->create('Magento\Customer\Model\Customer')->load($customerId);
        if(!$customer->getId())
            return array();

        $collection = $this->objectManager->create('Magento\Wishlist\Model\Wishlist')->getCollection();
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
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
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

        $this->helper->log('DYNAMIC WISHLIST PRODUCTS : limit ' . $limit . ' products : ' . $numItems . ', max per child : '. $maxPerChild);

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
                    $product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($product->getId());
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
            $fallbackIds = $this->recommnededHelper->getFallbackIds();

            foreach ($fallbackIds as $productId) {
                $product = $this->objectManager->create('Magento\Catalog\Model\Product')->load($productId);
                if($product->isSaleable())
                    $productsToDisplay[$product->getId()] = $product;
                //stop the limit was reached
                if (count($productsToDisplay) == $limit) {
                    break;
                }
            }
        }

        $this->helper->log('wishlist - loaded product to display ' . count($productsToDisplay));
        return $productsToDisplay;
    }

    /**
     * Product related items.
     *
     * @param $mode
     *
     * @return array
     */
    private  function _getRecommendedProduct($productModel, $mode)
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
        return $this->recommnededHelper->getDisplayType();

    }

    /**
     * Number of the colums.
     * @return int|mixed
     */
    public function getColumnCount()
    {
        return $this->recommnededHelper->getDisplayLimitByMode($this->getRequest()->getActionName());
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