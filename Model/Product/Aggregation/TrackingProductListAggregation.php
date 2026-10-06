<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Product\Aggregation;

use Dotdigitalgroup\Email\Model\Product\Provider\ProductProvider;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\Aggregation\ProductAggregationInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\Aggregation\ProductListAggregationInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class TrackingProductListAggregation
 *
 * This class aggregates product list data from various providers and implements the ProductAggregationInterface.
 */
class TrackingProductListAggregation implements ProductListAggregationInterface
{
    /**
     * @var ProductAggregationInterface
     */
    private $productAggregation;

    /**
     * @var ProductProvider
     */
    private $productProvider;

    /**
     * TrackingProductListAggregation constructor.
     *
     * @param ProductAggregationInterface $productAggregation
     * @param ProductProvider $productProvider
     */
    public function __construct(
        ProductAggregationInterface $productAggregation,
        ProductProvider $productProvider
    ) {
        $this->productAggregation = $productAggregation;
        $this->productProvider = $productProvider;
    }

    /**
     * Convert the product data to an array.
     *
     * @param array $products
     * @return array
     * @throws LocalizedException
     */
    public function toArray(array $products): array
    {
        $result = [];
        foreach ($products as $product) {
            $this->productProvider->setProduct($product);
            $result[] = $this->productAggregation->toArray();
        }
        return $result;
    }
}
