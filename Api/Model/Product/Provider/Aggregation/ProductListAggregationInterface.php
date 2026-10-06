<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Product\Provider\Aggregation;

/**
 * Interface ProductListAggregationInterface
 *
 * This interface defines the contract for product aggregation providers.
 */
interface ProductListAggregationInterface
{
    /**
     * Converts the object to an associative array.
     *
     * @param array $products
     * @return array The object data as an associative array.
     */
    public function toArray(array $products): array;
}
