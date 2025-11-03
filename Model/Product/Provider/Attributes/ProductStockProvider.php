<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product\Provider\Attributes;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductStockProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\ProductProviderInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;

class ProductStockProvider implements ProductStockProviderInterface
{
    /**
     * @var ProductProviderInterface
     */
    private $productProvider;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @param ProductProviderInterface $productProvider
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        ProductProviderInterface $productProvider,
        StockRegistryInterface $stockRegistry
    ) {
        $this->productProvider = $productProvider;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): string
    {
        /** @var Product $product */
        $product = $this->productProvider->getProduct();
        return $product && $product->isSalable() ? 'In stock' : 'Out of stock';
    }

    /**
     * @inheritDoc
     */
    public function getIsSalable(): bool
    {
        /** @var Product $product */
        $product = $this->productProvider->getProduct();
        return $product ? $product->isSalable() : false;
    }

    /**
     * @inheritDoc
     */
    public function getStockQuantity(): int
    {
        /** @var Product $product */
        $product = $this->productProvider->getProduct();
        if ($product instanceof ProductInterface) {
            $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
            return (int) $stockItem->getQty();
        }
        return 0;
    }
}
