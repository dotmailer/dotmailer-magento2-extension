<?php

namespace Dotdigitalgroup\Email\Api;

interface TierPriceFinderInterface
{
    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getTierPrices($product);
}
