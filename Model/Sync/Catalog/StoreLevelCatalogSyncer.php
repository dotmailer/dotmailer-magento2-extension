<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Sync\Catalog;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class StoreLevelCatalogSyncer implements CatalogSyncerInterface
{
    /**
     * @var Data
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
     * @var Catalog
     */
    private $syncCatalog;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * StoreLevelCatalogSyncer constructor.
     *
     * @param Data $helper
     * @param StoreCatalogSyncer $storeCatalogSyncer
     * @param Emulation $appEmulation
     * @param Catalog $syncCatalog
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helper,
        StoreCatalogSyncer $storeCatalogSyncer,
        Emulation $appEmulation,
        Catalog $syncCatalog,
        StoreManagerInterface $storeManager
    ) {
        $this->helper = $helper;
        $this->storeCatalogSyncer = $storeCatalogSyncer;
        $this->appEmulation = $appEmulation;
        $this->syncCatalog = $syncCatalog;
        $this->storeManager = $storeManager;
    }

    /**
     * Sync
     *
     * @param array $products
     *
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @see CatalogSyncerInterface::sync()
     */
    public function sync($products)
    {
        $stores = $this->storeManager->getStores();
        $syncedProducts = [];

        /** @var Store $store */
        foreach ($stores as $store) {
            $enabled = $this->helper->isEnabled($store->getWebsiteId());
            $catalogSyncEnabled = $this->helper->isCatalogSyncEnabled($store->getWebsiteId());
            $storeIsActive = $store->isActive();

            if (!$enabled || !$catalogSyncEnabled || !$storeIsActive) {
                continue;
            }

            $storeId = $store->getId();
            $this->appEmulation->startEnvironmentEmulation(
                $storeId,
                Area::AREA_FRONTEND,
                true
            );

            $catalogName = $this->syncCatalog->getStoreCatalogName(
                $store,
                CatalogSyncFactory::SYNC_CATALOG_STORE_LEVEL
            );
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
