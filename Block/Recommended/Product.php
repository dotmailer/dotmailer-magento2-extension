<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Product extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    public $priceHelper;
    /**
     * @var \Dotdigitalgroup\Email\Helper\Recommended
     */
    public $recommendedHelper;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    public $orderFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    public $productFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory
     */
    public $clientFactory;

    /**
     * Product constructor.
     *
     * @param \Magento\Sales\Model\OrderFactory                       $orderFactory
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory
     * @param \Magento\Catalog\Model\ProductFactory                   $productFactory
     * @param \Dotdigitalgroup\Email\Helper\Recommended               $recommended
     * @param \Dotdigitalgroup\Email\Helper\Data                      $helper
     * @param \Magento\Framework\Pricing\Helper\Data                  $priceHelper
     * @param \Magento\Catalog\Block\Product\Context                  $context
     * @param array                                                   $data
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory $clientFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderFactory      = $orderFactory;
        $this->clientFactory     = $clientFactory;
        $this->recommendedHelper = $recommended;
        $this->productFactory    = $productFactory;
        $this->helper            = $helper;
        $this->priceHelper       = $priceHelper;
    }

    /**
     * Get the products to display for recommendation.
     *
     * @return array
     */
    public function getLoadedProductCollection()
    {
        //products to be displayed for recommended pages
        $productsToDisplay = [];
        $productsToDisplayCounter = 0;
        $orderId = $this->getRequest()->getParam('order_id');
        //display mode based on the action name
        $mode = $this->getRequest()->getActionName();
        $orderModel = $this->orderFactory->create()
            ->load($orderId);
        //number of product items to be displayed
        $limit = $this->recommendedHelper
            ->getDisplayLimitByMode($mode);
        $orderItems = $orderModel->getAllItems();
        $numItems = count($orderItems);

        //no product found to display
        if ($numItems == 0 || !$limit) {
            return [];
        } elseif ($numItems > $limit) {
            $maxPerChild = 1;
        } else {
            $maxPerChild = number_format($limit / $numItems);
        }

        $this->helper->log(
            'DYNAMIC PRODUCTS : limit ' . $limit . ' products : '
            . $numItems . ', max per child : ' . $maxPerChild
        );

        foreach ($orderItems as $item) {
            $i = 0;
            //parent product
            $productModel = $item->getProduct();
            //check for product exists
            if ($productModel->getId()) {
                //get single product for current mode
                $recommendedProducts
                    = $this->_getRecommendedProduct($productModel, $mode);
                foreach ($recommendedProducts as $product) {
                    //check if still exists
                    if ($product->getId() && $productsToDisplayCounter < $limit
                        && $i <= $maxPerChild
                        && $product->isSaleable()
                        && !$product->getParentId()
                    ) {
                        //we have a product to display
                        $productsToDisplay[$product->getId()] = $product;
                        $i++;
                        $productsToDisplayCounter++;
                    }
                }
            }
            //have reached the limit don't loop for more
            if ($productsToDisplayCounter == $limit) {
                break;
            }
        }

        //check for more space to fill up the table with fallback products
        if ($productsToDisplayCounter < $limit) {
            $fallbackIds = $this->recommendedHelper->getFallbackIds();

            $productCollection = $this->productFactory->create()
                ->getCollection()
                ->addIdFilter($fallbackIds)
                ->addAttributeToSelect(
                    ['product_url', 'name', 'store_id', 'small_image', 'price']
                );

            foreach ($productCollection as $product) {
                if ($product->isSaleable()) {
                    $productsToDisplay[$product->getId()] = $product;
                }

                //stop the limit was reached
                //@codingStandardsIgnoreStart
                if (count($productsToDisplay) == $limit) {
                    break;
                }
                //@codingStandardsIgnoreEnd
            }
        }

        $this->helper->log('loaded product to display ' . count($productsToDisplay));

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
    public function _getRecommendedProduct($productModel, $mode)
    {
        //array of products to display
        $products = [];
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
     * Number of the columns.
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

    /**
     * @param $store
     *
     * @return mixed
     */
    public function getTextForUrl($store)
    {
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
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
        $this->setTemplate('Magento_Catalog::product/price/amount/default.phtml');

        $this->setProduct($product);

        return $this->toHtml();
    }
}
