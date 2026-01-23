<?php

namespace Dotdigitalgroup\Email\Api\Model\Product;

use Magento\Catalog\Model\Product;

interface PwaUrlFinderInterface
{
    /**
     * Build a PWA URL for the given product
     *
     * @param string $pwaUrl The base PWA URL configured for the website
     * @param Product $product The product to build the URL for
     * @return string The complete PWA URL for the product
     */
    public function buildPwaProductUrl(string $pwaUrl, Product $product): string;
}
