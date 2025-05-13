<?php

namespace Dotdigitalgroup\Email\Api;

use Magento\Catalog\Api\Data\ProductInterface;

interface TierPriceFinderInterface
{
    /**
     * Fetch product's tier prices.
     *
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return array
     *
     * @deprecated Fetch tier prices with a store id and customer group id.
     * @see \Dotdigitalgroup\Email\Model\Product\TierPriceFinder::getTierPricesByStoreAndGroup
     */
    public function getTierPrices($product);

    /**
     * Fetch product's tier prices.
     *
     * @param ProductInterface $product
     * @param int|null $storeId
     * @param int|null $customerGroupId
     *
     * @return array
     */
    public function getTierPricesByStoreAndGroup(
        ProductInterface $product,
        ?int $storeId,
        ?int $customerGroupId = null
    ): array;
}
