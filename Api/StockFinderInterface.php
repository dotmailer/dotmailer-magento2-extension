<?php

namespace Dotdigitalgroup\Email\Api;

interface StockFinderInterface
{
    /**
     * This function calculates the stock Quantity for each Product.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param int $websiteId
     * @return float
     */
    public function getStockQty($product, int $websiteId);
}
