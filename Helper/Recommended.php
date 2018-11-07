<?php

namespace Dotdigitalgroup\Email\Helper;

/**
 * Dynamic content configuration data and recommendation values.
 */
class Recommended extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_RELATED_PRODUCTS_TYPE = 'connector_dynamic_content/products/related_display_type';
    const XML_PATH_UPSELL_PRODUCTS_TYPE = 'connector_dynamic_content/products/upsell_display_type';
    const XML_PATH_CROSSSELL_PRODUCTS_TYPE = 'connector_dynamic_content/products/crosssell_display_type';
    const XML_PATH_BESTSELLER_PRODUCT_TYPE = 'connector_dynamic_content/products/bestsellers_display_type';
    const XML_PATH_MOSTVIEWED_PRODUCT_TYPE = 'connector_dynamic_content/products/most_viewed_display_type';
    const XML_PATH_RECENTLYVIEWED_PRODUCT_TYPE = 'connector_dynamic_content/products/recently_viewed_display_type';
    const XML_PATH_PRODUCTPUSH_TYPE = 'connector_dynamic_content/manual_product_push/display_type';

    const XML_PATH_RELATED_PRODUCTS_ITEMS = 'connector_dynamic_content/products/related_items_to_display';
    const XML_PATH_UPSELL_PRODUCTS_ITEMS = 'connector_dynamic_content/products/upsell_items_to_display';
    const XML_PATH_CROSSSELL_PRODUCTS_ITEMS = 'connector_dynamic_content/products/crosssell_items_to_display';
    const XML_PATH_BESTSELLER_PRODUCT_ITEMS = 'connector_dynamic_content/products/bestsellers_items_to_display';
    const XML_PATH_MOSTVIEWED_PRODUCT_ITEMS = 'connector_dynamic_content/products/most_viewed_items_to_display';
    const XML_PATH_RECENTLYVIEWED_PRODUCT_ITEMS = 'connector_dynamic_content/products/recently_viewed_items_to_display';

    const XML_PATH_PRODUCTPUSH_DISPLAY_ITEMS = 'connector_dynamic_content/manual_product_push/items_to_display';
    const XML_PATH_BESTSELLER_TIME_PERIOD = 'connector_dynamic_content/products/bestsellers_time_period';
    const XML_PATH_MOSTVIEWED_TIME_PERIOD = 'connector_dynamic_content/products/most_viewed_time_period';
    const XML_PATH_PRODUCTPUSH_ITEMS = 'connector_dynamic_content/manual_product_push/products_push_items';
    const XML_PATH_FALLBACK_PRODUCTS_ITEMS = 'connector_dynamic_content/fallback_products/product_ids';

    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    private $context;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $adapter;

    /**
     * @var \Zend_Date
     */
    private $date;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    public $catalog;

    /**
     * Recommended constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection $adapter
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Zend_Date $date
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $adapter,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Zend_Date $date,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog
    ) {
        $this->adapter      = $adapter;
        $this->helper       = $data;
        $this->context      = $context;
        $this->storeManager = $storeManager;
        $this->date = $date;
        $this->catalog    = $catalog;

        parent::__construct($context);
    }

    /**
     * Dispay type.
     *
     * @return string grid:list
     */
    public function getDisplayType()
    {
        $mode = $this->context->getRequest()->getActionName();

        $type = '';

        switch ($mode) {
            case 'related':
                $type = $this->getRelatedProductsType();
                break;
            case 'upsell':
                $type = $this->getUpsellProductsType();
                break;
            case 'crosssell':
                $type = $this->getCrosssellProductsType();
                break;
            case 'bestsellers':
                $type = $this->getBestSellerProductsType();
                break;
            case 'mostviewed':
                $type = $this->getMostViewedProductsType();
                break;
            case 'recentlyviewed':
                $type = $this->getRecentlyviewedProductsType();
                break;
            case 'push':
                $type = $this->getProductpushProductsType();
        }

        return $type;
    }

    /**
     * Get related product type.
     *
     * @return boolean|string
     */
    private function getRelatedProductsType()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_RELATED_PRODUCTS_TYPE
        );
    }

    /**
     * Get upsell product type.
     *
     * @return boolean|string
     */
    private function getUpsellProductsType()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_UPSELL_PRODUCTS_TYPE
        );
    }

    /**
     * Get crosssell product type.
     *
     * @return boolean|string
     */
    private function getCrosssellProductsType()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CROSSSELL_PRODUCTS_TYPE
        );
    }

    /**
     * Get bestseller product type.
     *
     * @return boolean|string
     */
    private function getBestSellerProductsType()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_BESTSELLER_PRODUCT_TYPE
        );
    }

    /**
     * Get most viewed product type.
     *
     * @return boolean|string
     */
    private function getMostViewedProductsType()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_MOSTVIEWED_PRODUCT_TYPE
        );
    }

    /**
     * Get recently viewed product type.
     *
     * @return boolean|string
     */
    private function getRecentlyviewedProductsType()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_RECENTLYVIEWED_PRODUCT_TYPE
        );
    }

    /**
     * Get product push product type.
     *
     * @return boolean|string
     */
    private function getProductpushProductsType()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_PRODUCTPUSH_TYPE);
    }

    /**
     * Limit of products displayed.
     *
     * @param string $mode
     *
     * @return int|mixed
     */
    public function getDisplayLimitByMode($mode)
    {
        $result = 0;

        switch ($mode) {
            case 'related':
                $result = $this->scopeConfig->getValue(
                    self::XML_PATH_RELATED_PRODUCTS_ITEMS
                );
                break;
            case 'upsell':
                $result = $this->scopeConfig->getValue(
                    self::XML_PATH_UPSELL_PRODUCTS_ITEMS
                );
                break;
            case 'crosssell':
                $result = $this->scopeConfig->getValue(
                    self::XML_PATH_CROSSSELL_PRODUCTS_ITEMS
                );
                break;
            case 'bestsellers':
                $result = $this->scopeConfig->getValue(
                    self::XML_PATH_BESTSELLER_PRODUCT_ITEMS
                );
                break;
            case 'mostviewed':
                $result = $this->scopeConfig->getValue(
                    self::XML_PATH_MOSTVIEWED_PRODUCT_ITEMS
                );
                break;
            case 'recentlyviewed':
                $result = $this->scopeConfig->getValue(
                    self::XML_PATH_RECENTLYVIEWED_PRODUCT_ITEMS
                );
                break;
            case 'push':
                $result = $this->scopeConfig->getValue(
                    self::XML_PATH_PRODUCTPUSH_DISPLAY_ITEMS
                );
        }

        return $result;
    }

    /**
     * Fallback product ids.
     *
     * @return array
     */
    public function getFallbackIds()
    {
        $fallbackIds = $this->scopeConfig->getValue(
            self::XML_PATH_FALLBACK_PRODUCTS_ITEMS
        );
        if ($fallbackIds) {
            return explode(
                ',',
                $this->scopeConfig->getValue(
                    self::XML_PATH_FALLBACK_PRODUCTS_ITEMS
                )
            );
        }

        return [];
    }

    /**
     * Get time period from config.
     *
     * @param string $config
     *
     * @return string
     */
    public function getTimeFromConfig($config)
    {
        $now = $this->date;
        $period = $this->processConfig($config);

        $sub = $this->processPeriod($period);

        if (isset($sub)) {
            $period = $now->sub(1, $sub);
        }

        return $period->toString(\Zend_Date::ISO_8601);
    }

    /**
     * @param string $config
     *
     * @return boolean|string
     */
    private function processConfig($config)
    {
        $period = null;

        if ($config == 'mostviewed') {
            $period = $this->scopeConfig->getValue(
                self::XML_PATH_MOSTVIEWED_TIME_PERIOD
            );
        } elseif ($config == 'bestsellers') {
            $period = $this->scopeConfig->getValue(
                self::XML_PATH_BESTSELLER_TIME_PERIOD
            );
        } elseif ($config == 'recentlyviewed') {
            $period = $this->scopeConfig->getValue(
                self::XML_PATH_MOSTVIEWED_TIME_PERIOD
            );
        }
        return $period;
    }

    /**
     * @param string $period
     *
     * @return null|string
     */
    private function processPeriod($period)
    {
        $sub = null;

        if ($period == 'week' || $period == 'W') {
            $sub = \Zend_Date::WEEK;
        } elseif ($period == 'month' || $period == 'M') {
            $sub = \Zend_Date::MONTH;
        } elseif ($period == 'year') {
            $sub = \Zend_Date::YEAR;
        } elseif ($period == 'D') {
            $sub = \Zend_Date::DAY;
        }
        return $sub;
    }

    /**
     * Get product push product ids.
     *
     * @return array
     */
    public function getProductPushIds()
    {
        $productIds = $this->scopeConfig->getValue(
            self::XML_PATH_PRODUCTPUSH_ITEMS
        );

        return explode(',', $productIds);
    }

    /**
     * Get products to display
     *
     * @param array $items
     * @param string $mode
     * @param int $productsToDisplayCounter
     * @param int $limit
     * @param int $maxPerChild
     *
     * @return array
     */
    public function getProductsToDisplay($items, $mode, &$productsToDisplayCounter, $limit, $maxPerChild)
    {
        $productsToDisplay = [];

        foreach ($items as $item) {
            //parent product
            $productModel = $item->getProduct();
            //check for product exists
            if ($productModel->getId()) {
                //get single product for current mode
                $recommendedProductIds = $this->getRecommendedProduct($productModel, $mode);
                $recommendedProducts = $this->catalog->getProductCollectionFromIds($recommendedProductIds);

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
     * Add recommended products
     *
     * @param int $productsToDisplayCounter
     * @param int $limit
     * @param int $maxPerChild
     * @param array $recommendedProducts
     * @param array $productsToDisplay
     *
     * @return null
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
     * Fill products to display
     *
     * @param array $productsToDisplay
     * @param array $productsToDisplayCounter
     * @param int $limit
     *
     * @return array
     */
    public function fillProductsToDisplay($productsToDisplay, &$productsToDisplayCounter, $limit)
    {
        $fallbackIds = $this->getFallbackIds();

        $productCollection = $this->catalog->getProductCollectionFromIds($fallbackIds);

        foreach ($productCollection as $product) {
            if ($product->isSaleable()) {
                $productsToDisplay[$product->getId()] = $product;
                $productsToDisplayCounter++;
            }

            //the limit was reached
            if ($productsToDisplayCounter == $limit) {
                break;
            }
        }
        return $productsToDisplay;
    }

    /**
     * Product related items.
     *
     * @param \Magento\Catalog\Model\Product $productModel
     * @param string $mode
     *
     * @return array
     */
    private function getRecommendedProduct($productModel, $mode)
    {
        //array of products to display
        $products = [];
        switch ($mode) {
            case 'related':
                $products = $productModel->getRelatedProductIds();
                break;
            case 'upsell':
                $products = $productModel->getUpSellProductIds();
                break;
            case 'crosssell':
                $products = $productModel->getCrossSellProductIds();
                break;
        }

        return $products;
    }
}
