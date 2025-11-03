<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Product\Provider\Aggregation;

use JsonSerializable;

/**
 * Interface ProductAggregationInterface
 *
 * This interface defines the contract for product aggregation providers.
 * It extends the JsonSerializable interface to allow objects to be serialized to JSON.
 */
interface ProductAggregationInterface extends JsonSerializable
{
    /**
     * Serializes the object to a JSON array.
     *
     * @return array The serialized data as an associative array.
     */
    public function jsonSerialize(): array;

    /**
     * Converts the object to an associative array.
     *
     * @return array The object data as an associative array.
     */
    public function toArray(): array;
}
