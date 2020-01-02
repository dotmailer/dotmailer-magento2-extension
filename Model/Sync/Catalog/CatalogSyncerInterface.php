<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

interface CatalogSyncerInterface
{
    /**
     * Sync
     *
     * @param array $products
     * @return array
     */
    public function sync($products);
}
