<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

/**
 * @implements CatalogSyncerInterface
 */
class DefaultLevelCatalogSyncer implements CatalogSyncerInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var StoreCatalogSyncer
     */
    private $storeCatalogSyncer;

    /**
     * DefaultLevelCatalogSyncer constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param StoreCatalogSyncer $storeCatalogSyncer
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        StoreCatalogSyncer $storeCatalogSyncer
    ) {
        $this->helper = $helper;
        $this->storeCatalogSyncer = $storeCatalogSyncer;
    }

    /**
     * Sync
     *
     * @see CatalogSyncerInterface::sync()
     * @param array $products
     * @return array
     */
    public function sync($products)
    {
        $enabled = $this->helper->isEnabled();
        $catalogSyncEnabled = $this->helper->isCatalogSyncEnabled();

        if (!$enabled || !$catalogSyncEnabled) {
            return [];
        }

        return $this->storeCatalogSyncer->syncByStore(
            $products,
            null,
            0,
            'Catalog_Default'
        );
    }
}
