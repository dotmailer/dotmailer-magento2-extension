<?php

namespace Dotdigitalgroup\Email\Api;

interface TierPriceFinderInterface
{
    /**
     * Fetch product's tier prices.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getTierPrices($product);
}
