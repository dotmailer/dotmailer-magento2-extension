<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Api\TierPriceFinderInterface;

class TierPriceFinder implements TierPriceFinderInterface
{
    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    public function getTierPrices($product)
    {
        return [];
    }
}
