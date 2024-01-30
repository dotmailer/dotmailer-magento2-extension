<?php

namespace Dotdigitalgroup\Email\Model\Product;

use Dotdigitalgroup\Email\Api\TierPriceFinderInterface;

class TierPriceFinder implements TierPriceFinderInterface
{
    /**
     * @inheritDoc
     */
    public function getTierPrices($product)
    {
        return [];
    }
}
