<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

/**
 * Wishlist products block
 *
 * @api
 */
class Wishlistproducts extends \Magento\Catalog\Block\Product\AbstractProduct
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
    public $recommnededHelper;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist
     */
    public $wishlist;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    public $catalog;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $customerResource;

    /**
     * Wishlistproducts constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Customer\Model\ResourceModel\Customer $customerResource
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $wishlist
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommended
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Customer\Model\ResourceModel\Customer $customerResource,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalog,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist $wishlist,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper            = $helper;
        $this->customerFactory   = $customerFactory;
        $this->recommnededHelper = $recommended;
        $this->priceHelper       = $priceHelper;
        $this->wishlist   = $wishlist;
        $this->catalog    = $catalog;
        $this->customerResource = $customerResource;
    }

    /**
     * Get wishlist items.
     *
     * @return array
     */
    public function _getWishlistItems()
    {
        $wishlist = $this->_getWishlist();
        if ($wishlist && ! empty($wishlist->getItemCollection())) {
            return $wishlist->getItemCollection();
        } else {
            return [];
        }
    }

    /**
     * Get wishlist for customer.
     *
     * @return array|bool
     */
    public function _getWishlist()
    {
        $customerId = (int) $this->getRequest()->getParam('customer_id');
        if (!$customerId) {
            return [];
        }

        $customer = $this->customerFactory->create();
        $this->customerResource->load($customer, $customerId);
        if (! $customer->getId()) {
            return [];
        }

        return $this->wishlist->getWishlistsForCustomer($customerId);
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
        if (! isset($params['customer_id']) ||
            ! isset($params['code']) ||
            ! $this->helper->isCodeValid($params['code'])
        ) {
            $this->helper->log('Wishlist no id or valid code is set');
            return [];
        }

        //products to be display for recommended pages
        //display mode based on the action name
        $mode = $this->getRequest()->getActionName();
        //number of product items to be displayed
        $limit = (int) $this->recommnededHelper->getDisplayLimitByMode($mode);
        $items = $this->_getWishlistItems();
        $numItems = count($items);

        //no product found to display
        if ($numItems == 0 || !$limit) {
            return [];
        } elseif ($numItems > $limit) {
            $maxPerChild = 1;
        } else {
            $maxPerChild = number_format($limit / $numItems);
        }

        $this->helper->log(
            'DYNAMIC WISHLIST PRODUCTS : limit ' . $limit . ' products : '
            . $numItems . ', max per child : ' . $maxPerChild
        );

        $productsToDisplayCounter = 0;
        $productsToDisplay = $this->getProductsToDisplay(
            $items,
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
            'wishlist - loaded product to display ' . count($productsToDisplay)
        );

        return $productsToDisplay;
    }

    /**
     * @param array $items
     * @param string $mode
     * @param int $productsToDisplayCounter
     * @param int $limit
     * @param int $maxPerChild
     *
     * @return array
     */
    private function getProductsToDisplay($items, $mode, &$productsToDisplayCounter, $limit, $maxPerChild)
    {
        $productsToDisplay = [];

        foreach ($items as $item) {
            //parent product
            $product = $item->getProduct();
            //check for product exists
            if ($product->getId()) {
                //get single product for current mode
                $recommendedProductIds = $this->getRecommendedProduct($product, $mode);
                $recommendedProducts = $this->catalog->getProductCollectionFromIds($recommendedProductIds);

                $this->addRecommendedProducts(
                    $productsToDisplayCounter,
                    $limit,
                    $maxPerChild,
                    $recommendedProducts,
                    $productsToDisplay,
                    $product
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
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return null
     */
    private function addRecommendedProducts(
        &$productsToDisplayCounter,
        $limit,
        $maxPerChild,
        $recommendedProducts,
        &$productsToDisplay,
        &$product
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
     * @param int $productsToDisplayCounter
     * @param int $limit
     *
     * @return array
     */
    private function fillProductsToDisplay($productsToDisplay, &$productsToDisplayCounter, $limit)
    {
        $fallbackIds = $this->recommnededHelper->getFallbackIds();
        $productCollection = $this->catalog->getProductCollectionFromIds($fallbackIds);

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

    /**
     * Diplay mode type.
     *
     * @return string|boolean
     */
    public function getMode()
    {
        return $this->recommnededHelper->getDisplayType();
    }

    /**
     * Number of the columns.
     *
     * @return int|mixed
     */
    public function getColumnCount()
    {
        return $this->recommnededHelper->getDisplayLimitByMode(
            $this->getRequest()->getActionName()
        );
    }

    /**
     * AC link to dynamic content.
     * @param null|string|bool|int|\Magento\Store\Api\Data\StoreInterface $store
     *
     * @return string|boolean
     */
    public function getTextForUrl($store)
    {
        $store = $this->_storeManager->getStore($store);

        return $store->getConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_LINK_TEXT
        );
    }
}
