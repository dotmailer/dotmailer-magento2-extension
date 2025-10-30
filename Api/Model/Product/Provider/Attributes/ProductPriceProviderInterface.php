<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes;

interface ProductPriceProviderInterface
{
    /**
     * Get the product price.
     *
     * @return float Returns the product price.
     */
    public function getPrice(): float;

    /**
     * Get the product price including tax.
     *
     * @return float Returns the product price including tax.
     */
    public function getPriceInclTax(): float;

    /**
     * Get the sale price of the product.
     *
     * @return float Returns the sale price of the product.
     */
    public function getSalePrice(): float;

    /**
     * Get the sale price of the product including tax.
     *
     * @return float Returns the sale price of the product including tax.
     */
    public function getSalePriceInclTax(): float;

    /**
     * Get the currency of the product.
     *
     * @return string Returns the currency of the product.
     */
    public function getCurrencyCode(): string;
}
