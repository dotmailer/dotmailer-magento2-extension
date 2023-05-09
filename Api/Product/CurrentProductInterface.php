<?php

namespace Dotdigitalgroup\Email\Api\Product;

use Magento\Catalog\Api\Data\ProductInterface;

interface CurrentProductInterface
{
    /**
     * Get current product
     *
     * @return ProductInterface|null
     */
    public function getProduct(): ?ProductInterface;

    /**
     * Get Magento product visibility
     *
     * @return int
     */
    public function getProductVisibility(): int;

    /**
     * Get product type
     *
     * @return string
     */
    public function getProductType(): string;
}
