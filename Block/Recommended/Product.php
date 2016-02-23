<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Product extends \Magento\Catalog\Block\Product\AbstractProduct
{

    public $helper;
    public $priceHelper;
    public $recommendedHelper;
    protected $_orderFactory;
    protected $_productFactory;
    protected $_clientFactory;


    /**
     * Product constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory
     * @param \Magento\Catalog\Model\ProductFactory                   $productFactory
     * @param \Dotdigitalgroup\Email\Helper\Recommended               $recommended
     * @param \Dotdigitalgroup\Email\Helper\Data                      $helper
     * @param \Magento\Framework\Pricing\Helper\Data                  $priceHelper
     * @param \Magento\Catalog\Block\Product\Context                  $context
     * @param array                                                   $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_clientFactory    = $clientFactory;
        $this->recommendedHelper = $recommended;
        $this->_productFactory   = $productFactory;
        $this->helper            = $helper;
        $this->priceHelper       = $priceHelper;
        $this->storeManager      = $this->_storeManager;
    }

    /**
     * get the products to display for table
     */
    public function getLoadedProductCollection()
    {
        //products to be diplayd for recommended pages
        $productsToDisplay = array();
        $orderId           = $this->getRequest()->getParam('order_id');
        //display mode based on the action name
        $mode       = $this->getRequest()->getActionName();
        $orderModel = $this->_orderFactory->create()
            ->load($orderId);
        //number of product items to be displayed
        $limit      = $this->recommendedHelper->create()
            ->getDisplayLimitByMode($mode);
        $orderItems = $orderModel->getAllItems();
        $numItems   = count($orderItems);

        //no product found to display
        if ($numItems == 0 || ! $limit) {
            return array();
        } elseif (count($orderItems) > $limit) {
            $maxPerChild = 1;
        } else {
            $maxPerChild = number_format($limit / count($orderItems));
        }

        $this->helper->log(
            'DYNAMIC PRODUCTS : limit ' . $limit . ' products : '
            . $numItems . ', max per child : ' . $maxPerChild
        );

        foreach ($orderItems as $item) {
            $i         = 0;
            $productId = $item->getProductId();
            //parent product
            $productModel = $this->_productFactory->create()
                ->load($productId);
            //check for product exists
            if ($productModel->getId()) {
                //get single product for current mode
                $recommendedProducts
                    = $this->_getRecommendedProduct($productModel, $mode);
                foreach ($recommendedProducts as $product) {
                    //load child product
                    $product = $this->_productFactory->create()
                        ->load($product->getId());
                    //check if still exists
                    if ($product->getId() && count($productsToDisplay) < $limit
                        && $i <= $maxPerChild
                        && $product->isSaleable()
                        && ! $product->getParentId()
                    ) {
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
            $fallbackIds = $this->recommendedHelper->getFallbackIds();

            foreach ($fallbackIds as $productId) {
                $product = $this->_productFactory->create()
                    ->load($productId);
                if ($product->isSaleable()) {
                    $productsToDisplay[$product->getId()] = $product;
                }
                //stop the limit was reached
                if (count($productsToDisplay) == $limit) {
                    break;
                }
            }
        }

        $this->helper->log(
            'loaded product to display '
            . count($productsToDisplay)
        );

        return $productsToDisplay;
    }

    /**
     * Product related items.
     *
     * @param $productModel
     * @param $mode
     *
     * @return array
     */
    protected function _getRecommendedProduct($productModel, $mode)
    {
        //array of products to display
        $products = array();
        switch ($mode) {
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
        return $this->recommendedHelper->getDisplayType();

    }

    /**
     * Number of the colums.
     *
     * @return int|mixed
     */
    public function getColumnCount()
    {
        return $this->recommendedHelper->getDisplayLimitByMode(
            $this->getRequest()
                ->getActionName()
        );
    }

    public function getTextForUrl($store)
    {
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}