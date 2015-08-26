<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Product extends \Magento\Framework\View\Element\Template
{
	/**
	 * Slot div name.
	 * @var string
	 */
	public $slot;

	public $helper;
	public $priceHelper;
	public $scopeManager;
	public $objectManager;


	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\Pricing\Helper\Data $priceHelper,
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	)
	{
		parent::__construct( $context, $data );
		$this->helper = $helper;
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
        $orderId = $this->getRequest()->getParam('order_id');
	    //display mode based on the action name
        $mode  = $this->getRequest()->getActionName();
        $orderModel = $this->objectManager->create('Magento\Sales\Model\Order')->load($orderId);
	    //number of product items to be displayed
        $limit      = $this->objectManager->create('Dotdigitalgroup\Email\Helper\Recommended')->getDisplayLimitByMode($mode);
        $orderItems = $orderModel->getAllItems();
	    $numItems = count($orderItems);

	    //no product found to display
	    if ($numItems == 0 || ! $limit) {
		    return array();
	    }elseif (count($orderItems) > $limit) {
            $maxPerChild = 1;
        } else {
            $maxPerChild = number_format($limit / count($orderItems));
        }

		$this->helper->log('DYNAMIC PRODUCTS : limit ' . $limit . ' products : ' . $numItems . ', max per child : '. $maxPerChild);

        foreach ($orderItems as $item) {
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
            $fallbackIds = $this->objectManager->create('Dotdigitalgroup\Email\Helper\Recommended')->getFallbackIds();

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

        $this->helper->log('loaded product to display ' . count($productsToDisplay));
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
        return $this->objectManager->create('Dotdigitalgroup\Email\Helper\Recommended')->getDisplayType();

    }

	/**
	 * Number of the colums.
	 * @return int|mixed
	 */
	public function getColumnCount()
    {
        return $this->objectManager->create('Dotdigitalgroup\Email\Helper\Recommended')->getDisplayLimitByMode($this->getRequest()->getActionName());
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


	/**
	 * Nosto products data.
	 * @return object
	 */
	public function getNostoProducts()
	{
		$client = $this->objectManager->create('Dotdigitalgroup\Email\Model\Apiconnector\Client');
		//slot name, div id
		$slot  = $this->getRequest()->getParam('slot', false);

		//email recommendation
		$email = $this->getRequest()->getParam('email', false);

		//no valid data for nosto recommendation
		if (!$slot || ! $email)
			return false;
		else
			$this->slot = $slot;

		//html data from nosto
		$data = $client->getNostoProducts($slot, $email);

		//check for valid response
		if (! isset($data->$email) && !isset($data->$email->$slot))
			return false;
		return $data->$email->$slot;
	}

	/**
	 * Slot name.
	 * Should be called after getNostoProducts.
	 * @return string
	 */
	public function getSlotName()
	{
		return $this->slot;
	}

	public function getTextForUrl($store)
	{
		$store = $this->_storeManager->getStore($store);
		return $store->getConfig(
			\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
		);
	}
}