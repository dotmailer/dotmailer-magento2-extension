<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

interface CatalogSyncerInterface
{
    const MEGA_BATCH_SIZE = 10000;

    /**
     * Sync
     *
     * @param array $products
     * @return array
     */
    public function sync($products);
}
