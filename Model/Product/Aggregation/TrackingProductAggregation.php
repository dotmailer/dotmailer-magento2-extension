<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product\Aggregation;

use Dotdigitalgroup\Email\Api\Model\Product\Provider\Aggregation\ProductAggregationInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductGeneralProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductMediaProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductPriceProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductStockProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductTaxonomyProviderInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class TrackingProductAggregation
 *
 * This class aggregates product data from various providers and implements the ProductAggregationInterface.
 */
class TrackingProductAggregation implements ProductAggregationInterface
{
    /**
     * @var ProductGeneralProviderInterface
     */
    private $generalProvider;

    /**
     * @var ProductStockProviderInterface
     */
    private $stockProvider;

    /**
     * @var ProductPriceProviderInterface
     */
    private $priceProvider;

    /**
     * @var ProductTaxonomyProviderInterface
     */
    private $taxonomyProvider;

    /**
     * @var ProductMediaProviderInterface
     */
    private $mediaProvider;

    /**
     * TrackingProductAggregation constructor.
     *
     * @param ProductGeneralProviderInterface $generalProductProvider
     * @param ProductStockProviderInterface $stockProvider
     * @param ProductPriceProviderInterface $productProvider
     * @param ProductTaxonomyProviderInterface $productTaxonomyProvider
     * @param ProductMediaProviderInterface $productMediaProvider
     */
    public function __construct(
        ProductGeneralProviderInterface $generalProductProvider,
        ProductStockProviderInterface $stockProvider,
        ProductPriceProviderInterface $productProvider,
        ProductTaxonomyProviderInterface $productTaxonomyProvider,
        ProductMediaProviderInterface $productMediaProvider
    ) {
        $this->generalProvider = $generalProductProvider;
        $this->stockProvider = $stockProvider;
        $this->priceProvider = $productProvider;
        $this->taxonomyProvider = $productTaxonomyProvider;
        $this->mediaProvider = $productMediaProvider;
    }

    /**
     * Convert the product data to an array.
     *
     * @return array
     * @throws LocalizedException
     */
    public function toArray(): array
    {
        return [
            'productId' => (string)$this->getId(),
            'name' => $this->getName(),
            'url' => $this->getUrl(),
            'stock' => $this->getStock(),
            'currency' => $this->getCurrencyCode(),
            'status' => $this->getStatus(),
            'price' => $this->getPrice(),
            'priceInclTax' => $this->getPriceInclTax(),
            'sku' => $this->getSku(),
            'brand' => $this->getBrand(),
            'categories' => $this->getCategoryNames(),
            'imageUrl' => $this->getImagePath(),
            'description' => $this->getDescription(),
            'extraData' => [
                'type' => $this->getProductType()
            ],
            'salePrice' => $this->getSalePrice(),
            'salePriceInclTax' => $this->getSalePriceInclTax()
        ];
    }

    /**
     * Convert the product data to a JSON serializable array.
     *
     * @return array
     * @throws LocalizedException
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Get the product URL.
     *
     * @return string
     */
    private function getUrl(): string
    {
        return $this->generalProvider->getUrl() ?? '';
    }

    /**
     * Get the product status.
     *
     * @return string
     */
    private function getStatus(): string
    {
        return $this->stockProvider->getStatus() ?? '';
    }

    /**
     * Get the product price.
     *
     * @return float
     */
    private function getPrice(): float
    {
        return $this->priceProvider->getPrice() ?? 0.00;
    }

    /**
     * Get the product price including tax.
     *
     * @return float
     */
    private function getPriceInclTax(): float
    {
        return $this->priceProvider->getPriceInclTax() ?? 0.00;
    }

    /**
     * Get the product special price.
     *
     * @return float
     */
    private function getSalePrice(): float
    {
        return $this->priceProvider->getSalePrice() ?? 0.00;
    }

    /**
     * Get the product special price including tax.
     *
     * @return float
     */
    private function getSalePriceInclTax(): float
    {
        return $this->priceProvider->getSalePriceInclTax() ?? 0.00;
    }

    /**
     * Get the product SKU.
     *
     * @return string
     */
    private function getSku(): string
    {
        return $this->generalProvider->getSku() ?? '';
    }

    /**
     * Get the product brand.
     *
     * @return string
     */
    private function getBrand(): string
    {
        return $this->taxonomyProvider->getBrand() ?? '';
    }

    /**
     * Get category Names as a string.
     *
     * @return array
     * @throws LocalizedException
     */
    private function getCategoryNames(): array
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection */
        $categoryCollection = $this->taxonomyProvider->getCategories();
        $categoryNames = array_reduce($categoryCollection->getItems(), function ($acc, $category) {
            $acc[] = $category->getName();
            return $acc;
        }, []);
        return $categoryNames;
    }

    /**
     * Get the product image path.
     *
     * @return string
     */
    private function getImagePath(): string
    {
        return $this->mediaProvider->getImagePath() ?? '';
    }

    /**
     * Get the product description.
     *
     * @return string
     */
    private function getDescription(): string
    {
        return $this->generalProvider->getDescription() ?? '';
    }

    /**
     * Get the product ID.
     *
     * @return int
     */
    private function getId(): int
    {
        return $this->generalProvider->getId() ?? -1;
    }

    /**
     * Get the product name.
     *
     * @return string
     */
    private function getName(): string
    {
        return $this->generalProvider->getName() ?? '';
    }

    /**
     * Get the product type.
     *
     * @return string
     */
    private function getProductType(): string
    {
        return $this->generalProvider->getType() ?? '';
    }

    /**
     * Get the product currency.
     *
     * @return string
     */
    private function getCurrencyCode(): string
    {
        return $this->priceProvider->getCurrencyCode() ?? '';
    }

    /**
     * Get the stock quantity.
     *
     * @return int
     */
    private function getStock(): int
    {
        return $this->stockProvider->getStockQuantity() ?? 0;
    }
}
