<?php

namespace Dotdigitalgroup\Email\Block\Recommended;

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
     * @var \Magento\Wishlist\Model\WishlistFactory
     */
    public $wishlistFactory;
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    public $productFactory;

    /**
     * Wishlistproducts constructor.
     *
     * @param \Magento\Catalog\Model\ProductFactory     $productFactory
     * @param \Magento\Wishlist\Model\WishlistFactory   $wishlistFactory
     * @param \Magento\Customer\Model\CustomerFactory   $customerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data        $helper
     * @param \Magento\Framework\Pricing\Helper\Data    $priceHelper
     * @param \Dotdigitalgroup\Email\Helper\Recommended $recommended
     * @param \Magento\Catalog\Block\Product\Context    $context
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Dotdigitalgroup\Email\Helper\Recommended $recommended,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper            = $helper;
        $this->customerFactory   = $customerFactory;
        $this->recommnededHelper = $recommended;
        $this->priceHelper       = $priceHelper;
        $this->wishlistFactory   = $wishlistFactory;
        $this->productFactory    = $productFactory;
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
     * @return array|\Magento\Framework\DataObject
     */
    public function _getWishlist()
    {
        $customerId = $this->getRequest()->getParam('customer_id');
        if (!$customerId) {
            return [];
        }

        $customer = $this->customerFactory->create()
            ->load($customerId);
        if (!$customer->getId()) {
            return [];
        }

        $collection = $this->wishlistFactory->create()
            ->getCollection()
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder('updated_at', 'DESC');
        $collection->getSelect()->limit(1);

        if ($collection->getSize()) {
            //@codingStandardsIgnoreStart
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        } else {
            return [];
        }
    }

    /**
     * Get the products to display for table.
     *
     * @return array
     */
    public function getLoadedProductCollection()
    {
        //products to be display for recommended pages
        $productsToDisplay = [];
        $productsToDisplayCounter = 0;
        //display mode based on the action name
        $mode = $this->getRequest()->getActionName();
        //number of product items to be displayed
        $limit = $this->recommnededHelper->getDisplayLimitByMode($mode);
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

        foreach ($items as $item) {
            $i = 0;
            //parent product
            $product = $item->getProduct();
            //check for product exists
            if ($product->getId()) {
                //get single product for current mode
                $recommendedProducts = $this->_getRecommendedProduct(
                    $product,
                    $mode
                );
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
            $fallbackIds = $this->recommnededHelper->getFallbackIds();

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

        $this->helper->log(
            'wishlist - loaded product to display ' . count($productsToDisplay)
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
        return $this->recommnededHelper->getDisplayType();
    }

    /**
     * Number of the colums.
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
