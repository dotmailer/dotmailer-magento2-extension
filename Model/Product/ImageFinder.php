<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

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
     * ImageFinder constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
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
}
