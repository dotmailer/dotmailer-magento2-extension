<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes;

interface ProductStockProviderInterface
{
    /**
     * Get the status of the product.
     *
     * @return string Returns the status of the product.
     */
    public function getStatus(): string;

    /**
     * Check if the product is salable.
     *
     * @return bool Returns true if the product is salable, false otherwise.
     */
    public function getIsSalable(): bool;

    /**
     * Get the stock quantity of the product.
     *
     * @return int
     */
    public function getStockQuantity(): int;
}
