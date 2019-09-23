<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

interface CatalogSyncerInterface
{
    /**
     * Sync
     *
     * @param array $products
     * @return int
     */
    public function sync($products);
}
