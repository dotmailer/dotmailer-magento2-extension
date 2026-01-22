<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product\Provider;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Data as CatalogHelper;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Store\Model\StoreManagerInterface;

class LowestPriceProductFinder
{
    /**
     * @var CatalogHelper
     */
    private $catalogHelper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * $useSpecialPrice is defined in di.xml to determine if special price should be considered
     * @var bool
     */
    private $useSpecialPrice;

    /**
     * @param CatalogHelper $catalogHelper
     * @param StoreManagerInterface $storeManager
     * @param bool $useSpecialPrice
     */
    public function __construct(
        CatalogHelper $catalogHelper,
        StoreManagerInterface $storeManager,
        bool $useSpecialPrice = false
    ) {
        $this->catalogHelper = $catalogHelper;
        $this->storeManager = $storeManager;
        $this->useSpecialPrice = $useSpecialPrice;
    }

    /**
     * Find the lowest priced product
     *
     * @return ProductInterface|null
     */
    public function findLowestPricedProduct(): ?ProductInterface
    {
        $product = $this->catalogHelper->getProduct();

        if (!$product || ($product->getTypeId() !== 'configurable' && $product->getTypeId() !== 'grouped')) {
            return $product;
        }

        $childProducts = $this->getChildProducts($product);
        $storeId = (int)$this->storeManager->getStore()->getId();

        return $this->findLowestPrice($childProducts, $storeId);
    }

    /**
     * Get child products based on product type
     *
     * @param Product $product
     * @return array
     */
    private function getChildProducts(Product $product): array
    {
        if ($product->getTypeId() === 'configurable') {
            /** @var Configurable $configurableProductInstance */
            $configurableProductInstance = $product->getTypeInstance();
            return $configurableProductInstance->getUsedProducts($product);
        }

        return $product->getTypeInstance()->getAssociatedProducts($product);
    }

    /**
     * Find the product with the lowest price
     *
     * @param array $childProducts
     * @param int $storeId
     * @return ProductInterface|null
     */
    private function findLowestPrice(array $childProducts, int $storeId): ?ProductInterface
    {
        $lowestPricedProduct = null;
        $lowestPrice = null;
        $lowestSalePricedProduct = null;
        $lowestSalePrice = null;

        /** @var Product $childProduct */
        foreach ($childProducts as $childProduct) {
            if ($storeId && !in_array($storeId, $childProduct->getStoreIds())) {
                continue;
            }

            $childPrice = $childProduct->getPrice();
            if ($childPrice !== null && ($lowestPrice === null || $childPrice < $lowestPrice)) {
                $lowestPricedProduct = $childProduct;
                $lowestPrice = $childPrice;
            }

            $childSpecialPrice = $childProduct->getSpecialPrice();
            if ($childSpecialPrice !== null && ($lowestSalePrice === null || $childSpecialPrice < $lowestSalePrice)) {
                $lowestSalePricedProduct = $childProduct;
                $lowestSalePrice = $childSpecialPrice;
            }
        }

        if ($this->useSpecialPrice) {
            return $lowestSalePricedProduct ?? $lowestPricedProduct;
        }
        return $lowestPricedProduct;
    }
}
