<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;

class StoreLevelCatalogSyncer implements CatalogSyncerInterface
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
     * StoreLevelCatalogSyncer constructor.
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @see CatalogSyncerInterface::sync()
     */
    public function sync($products)
    {
        $stores = $this->helper->getStores();
        $syncedProducts = [];

        /** @var \Magento\Store\Model\Store $store */
        foreach ($stores as $store) {
            $enabled = $this->helper->isEnabled($store->getWebsiteId());
            $catalogSyncEnabled = $this->helper->isCatalogSyncEnabled($store->getWebsiteId());

            if (!$enabled || !$catalogSyncEnabled) {
                continue;
            }

            $storeId = $store->getId();
            $this->appEmulation->startEnvironmentEmulation(
                $storeId,
                Area::AREA_FRONTEND,
                true
            );

            $catalogName = 'Catalog_' . $store->getWebsite()->getCode() . '_' . $store->getCode();
            $syncedProducts += $this->storeCatalogSyncer->syncByStore(
                $products,
                $storeId,
                $store->getWebsiteId(),
                $catalogName
            );

            $this->appEmulation->stopEnvironmentEmulation();
        }

        return $syncedProducts;
    }
}
