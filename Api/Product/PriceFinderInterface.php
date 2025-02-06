<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Api\Product;

use Magento\Catalog\Model\Product;

interface PriceFinderInterface
{
    /**
     * Get price.
     *
     * @param Product $product
     * @param int|null $storeId
     *
     * @return float
     */
    public function getPrice(Product $product, ?int $storeId): float;

    /**
     * Get price including tax.
     *
     * @param Product $product
     * @param int|null $storeId
     *
     * @return float
     */
    public function getPriceInclTax(Product $product, ?int $storeId): float;

    /**
     * Get special price.
     *
     * @param Product $product
     * @param int|null $storeId
     *
     * @return float
     */
    public function getSpecialPrice(Product $product, ?int $storeId): float;

    /**
     * Get special price including tax.
     *
     * @param Product $product
     * @param int|null $storeId
     *
     * @return float
     */
    public function getSpecialPriceInclTax(Product $product, ?int $storeId): float;
}
