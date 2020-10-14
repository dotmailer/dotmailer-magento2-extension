<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;

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
     * @var Emulation
     */
    private $appEmulation;

    /**
     * DefaultLevelCatalogSyncer constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param StoreCatalogSyncer $storeCatalogSyncer
     * @param Emulation $appEmulation
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        StoreCatalogSyncer $storeCatalogSyncer,
        Emulation $appEmulation
    ) {
        $this->helper = $helper;
        $this->storeCatalogSyncer = $storeCatalogSyncer;
        $this->appEmulation = $appEmulation;
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

        $this->appEmulation->startEnvironmentEmulation(0, Area::AREA_FRONTEND, true);

        $syncedProducts = $this->storeCatalogSyncer->syncByStore(
            $products,
            null,
            0,
            'Catalog_Default'
        );

        $this->appEmulation->stopEnvironmentEmulation();

        return $syncedProducts;
    }
}
