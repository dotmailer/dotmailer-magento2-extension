<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Quoteproducts extends \Magento\Catalog\Block\Product\AbstractProduct
{
	public $helper;
	public $priceHelper;
	public $scopeManager;
	public $objectManager;
	protected $_recommendedHelper;


	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Dotdigitalgroup\Email\Helper\Recommended $recommendedHelper,
		\Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Catalog\Block\Product\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	)
	{
		parent::__construct( $context, $data );
		$this->helper = $helper;
		$this->_recommendedHelper = $recommendedHelper;
		$this->priceHelper = $priceHelper;
		$this->scopeManager = $scopeConfig;
		$this->storeManager = $this->_storeManager;
		$this->objectManager = $objectManagerInterface;
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
        $quoteModel = $this->objectManager->create('Magento\Quote\Model\Quote')->load($quoteId);
        //number of product items to be displayed
        $limit      = $this->_recommendedHelper->getDisplayLimitByMode($mode);
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

        $this->helper->log('DYNAMIC QUOTE PRODUCTS : limit ' . $limit . ' products : ' . $numItems . ', max per child : '. $maxPerChild);

        foreach ($quoteItems as $item) {
            $i = 0;
            $productId = $item->getProductId();
            //parent product
            $productModel = $this->objectManager->create('Magento\Catalog\Model\Product')->load($productId);
            //check for product exists
            if ($productModel->getId()) {
                //get single product for current mode
                $recommendedProducts = $this->_getRecommendedProduct($productModel, $mode);
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
            $fallbackIds = $this->_recommendedHelper->getFallbackIds();

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

        $this->helper->log('quote - loaded product to display ' . count($productsToDisplay));
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
        return $this->_recommendedHelper->getDisplayType();

    }

    /**
     * Number of the colums.
     * @return int|mixed
     * @throws Exception
     */
    public function getColumnCount()
    {
        return $this->_recommendedHelper->getDisplayLimitByMode($this->getRequest()->getActionName());
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

    public function getTextForUrl($store)
    {
        $store = $this->_storeManager->getStore($store);
        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}