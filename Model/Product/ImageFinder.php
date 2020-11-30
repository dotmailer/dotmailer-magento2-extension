<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Configuration\Item\ItemProductResolver as ConfigurableProductResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\GroupedProduct\Model\Product\Configuration\Item\ItemProductResolver as GroupedProductResolver;

class ImageFinder
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ParentFinder
     */
    private $parentFinder;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * ImageFinder constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param ParentFinder $parentFinder
     * @param Logger $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig,
        ParentFinder $parentFinder,
        Logger $logger
    ) {
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->parentFinder = $parentFinder;
        $this->logger = $logger;
    }

    /**
     * Get product image URL. We respect the "Configurable Product Image" setting in determining
     * which image to retrieve.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param \Magento\Store\Api\Data\StoreInterface $store
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductImageUrl($item, $store)
    {
        $url = "";
        $base = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true) . 'catalog/product';

        $configurableProductImage = $this->scopeConfig->getValue(
            'checkout/cart/configurable_product_image',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store->getId()
        );

        if ($configurableProductImage === "itself") {

            // Use item SKU to retrieve properties of configurable products
            $id = $item->getProduct()->getIdBySku($item->getSku());
            $product = $this->productRepository->getById($id, false, $item->getStoreId());

            if ($product->getThumbnail() !== "no_selection") {
                return $base . $product->getThumbnail();
            }
        }

        // Parent thumbnail
        if ($item->getProduct()->getThumbnail() !== "no_selection") {
            $url = $base . $item->getProduct()->getThumbnail();
        }

        return $url;
    }

    /**
     * Get an image URL for a product in the cart context.
     * We respect the "Configurable Product Image" setting in determining
     * which image to retrieve.
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param $store
     * @return string
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCartImageUrl($item, $store)
    {
        $url = "";
        $base = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true) . 'catalog/product';

        switch ($item->getProductType()) {
            case 'configurable':
                $productId = $this->getProductIdForConfigurableType($item, $store->getId());
                break;
            case 'grouped':
                $productId = $this->getProductIdForGroupedType($item, $store->getId());
                break;
            default:
                $productId = $item->getProduct()->getId();
        }

        $product = $this->productRepository->getById($productId, false, $item->getStoreId());

        if ($product->getThumbnail() !== "no_selection") {
            $url = $base . $product->getThumbnail();
        }

        return $url;
    }

    /**
     * @param $item
     * @param $storeId
     * @return string|int
     */
    private function getProductIdForConfigurableType($item, $storeId)
    {
        $configurableProductImage = $this->scopeConfig->getValue(
            ConfigurableProductResolver::CONFIG_THUMBNAIL_SOURCE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($configurableProductImage === "itself") {
            // Use item SKU to retrieve properties of configurable child product
            return $item->getProduct()->getIdBySku($item->getSku());
        }

        // Parent product id
        return $item->getProduct()->getId();
    }

    /**
     * @param $item
     * @param $storeId
     * @return string|int
     */
    private function getProductIdForGroupedType($item, $storeId)
    {
        $groupedProductImage = $this->scopeConfig->getValue(
            GroupedProductResolver::CONFIG_THUMBNAIL_SOURCE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($groupedProductImage === 'itself') {
            return $item->getProduct()->getId();
        }

        $parentProduct = $this->parentFinder->getParentProduct($item->getProduct(), 'grouped');
        if (!$parentProduct) {
            $this->logger->debug(
                'Parent product for grouped item ID ' . $item->getProduct()->getId() . ' not found.'
            );
            return $item->getProduct()->getId();
        }
        return $parentProduct->getId();
    }
}
