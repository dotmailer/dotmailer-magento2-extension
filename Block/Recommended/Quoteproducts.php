<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

class Quoteproducts extends \Magento\Catalog\Block\Product\AbstractProduct
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
     * @var \Magento\Quote\Model\QuoteFactory
     */
    public $quoteFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory
     */
    public $catalogFactory;

    /**
     * Quoteproducts constructor.
     *
     * @param \Magento\Quote\Model\QuoteFactory                             $quoteFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                            $helper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory     $catalogFactory
     * @param \Dotdigitalgroup\Email\Helper\Recommended                     $recommendedHelper
     * @param \Magento\Framework\Pricing\Helper\Data                        $priceHelper
     * @param \Magento\Catalog\Block\Product\Context                        $context
     * @param array                                                         $data
     */
    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogFactory,
        \Dotdigitalgroup\Email\Helper\Recommended $recommendedHelper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper            = $helper;
        $this->catalogFactory    = $catalogFactory;
        $this->quoteFactory      = $quoteFactory;
        $this->recommendedHelper = $recommendedHelper;
        $this->priceHelper       = $priceHelper;
    }

    /**
     * Get the products to display for table.
     *
     * @return array
     */
    public function getLoadedProductCollection()
    {
        $params = $this->getRequest()->getParams();
        //check for param code and id
        if (! isset($params['quote_id']) ||
            ! isset($params['code']) ||
            ! $this->helper->isCodeValid($params['code'])
        )
        {
            $this->helper->log('Quote recommendation no id or valid code is set');
            return [];
        }

        //products to be diplayd for recommended pages
        $quoteId = (int) $this->getRequest()->getParam('quote_id');
        //display mode based on the action name
        $mode = $this->getRequest()->getActionName();
        $quoteModel = $this->quoteFactory->create();
        $quoteModel = $quoteModel->getResource()->load($quoteModel, $quoteId);
        //number of product items to be displayed
        $limit = $this->recommendedHelper->getDisplayLimitByMode($mode);
        $quoteItems = $quoteModel->getAllItems();
        $numItems = count($quoteItems);

        //no product found to display
        if ($numItems == 0 || !$limit) {
            return [];
        } elseif ($numItems > $limit) {
            $maxPerChild = 1;
        } else {
            $maxPerChild = number_format($limit / $numItems);
        }

        $this->helper->log(
            'DYNAMIC QUOTE PRODUCTS : limit ' . $limit . ' products : '
            . $numItems . ', max per child : ' . $maxPerChild
        );

        $productsToDisplayCounter = 0;
        $productsToDisplay = $this->getProductsToDisplay(
            $quoteItems,
            $mode,
            $productsToDisplayCounter,
            $limit,
            $maxPerChild
        );

        //check for more space to fill up the table with fallback products
        if ($productsToDisplayCounter < $limit) {
            $productsToDisplay = $this->fillProductsToDisplay($productsToDisplay, $productsToDisplayCounter, $limit);
        }

        $this->helper->log(
            'quote - loaded product to display ' . count($productsToDisplay)
        );

        return $productsToDisplay;
    }

    /**
     * @param $quoteItems
     * @param $mode
     * @param $productsToDisplayCounter
     * @param $limit
     * @param $maxPerChild
     *
     * @return array
     */
    private function getProductsToDisplay($quoteItems, $mode, &$productsToDisplayCounter, $limit, $maxPerChild)
    {
        $productsToDisplay = [];

        foreach ($quoteItems as $item) {
            //parent product
            $productModel = $item->getProduct();

            //check for product exists
            if ($productModel->getId()) {
                //get single product for current mode
                $recommendedProducts = $this->_getRecommendedProduct($productModel, $mode);

                $this->addRecommendedProducts(
                    $productsToDisplayCounter,
                    $limit,
                    $maxPerChild,
                    $recommendedProducts,
                    $productsToDisplay
                );
            }
            //have reached the limit don't loop for more
            if ($productsToDisplayCounter == $limit) {
                break;
            }
        }
        return $productsToDisplay;
    }

    /**
     * @param $productsToDisplayCounter
     * @param $limit
     * @param $maxPerChild
     * @param $recommendedProducts
     * @param $productsToDisplay
     */
    private function addRecommendedProducts(
        &$productsToDisplayCounter,
        $limit,
        $maxPerChild,
        $recommendedProducts,
        &$productsToDisplay
    ) {
        $i = 0;

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

    /**
     * @param $productsToDisplay
     * @param $productsToDisplayCounter
     * @param $limit
     *
     * @return mixed
     */
    private function fillProductsToDisplay($productsToDisplay, &$productsToDisplayCounter, $limit)
    {
        $fallbackIds = $this->recommendedHelper->getFallbackIds();

            $productCollection = $this->catalogFactory->create()
                ->getProductCollectionFromIds($fallbackIds);

        foreach ($productCollection as $product) {
            if ($product->isSaleable()) {
                $productsToDisplay[$product->getId()] = $product;
                $productsToDisplayCounter++;
            }

            //stop the limit was reached
            if ($productsToDisplayCounter == $limit) {
                break;
            }
        }
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
     * Number of the colums.
     *
     * @return int|mixed
     *
     * @throws \Exception
     */
    public function getColumnCount()
    {
        return $this->recommendedHelper->getDisplayLimitByMode(
            $this->getRequest()->getActionName()
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
}
