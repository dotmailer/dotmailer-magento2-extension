<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\App\Emulation;
use Dotdigitalgroup\Email\Model\Sync\Catalog;

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
     * @param array $products
     *
     * @return array
     * @throws NoSuchEntityException
     * @see CatalogSyncerInterface::sync()
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
            Catalog::DEFAULT_CATALOG_NAME
        );

        $this->appEmulation->stopEnvironmentEmulation();

        return $syncedProducts;
    }
}
