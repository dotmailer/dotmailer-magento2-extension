<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Magento\Store\Model\App\Emulation;

class StoreLevelCatalogSyncer implements CatalogSyncerInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    private $catalogResource;

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
     * @param \Dotdigitalgroup\Email\Helper\Data                 $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource
     * @param StoreCatalogSyncer                                 $storeCatalogSyncer
     * @param Emulation                                          $appEmulation
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $catalogResource,
        StoreCatalogSyncer $storeCatalogSyncer,
        Emulation $appEmulation
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->catalogResource = $catalogResource;
        $this->storeCatalogSyncer = $storeCatalogSyncer;
        $this->appEmulation = $appEmulation;
    }

    /**
     * Sync
     *
     * @return int
     */
    public function sync()
    {
        $limit = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );
        $stores = $this->helper->getStores();
        $products = [];

        foreach ($stores as $store) {
            $enabled = $this->helper->isEnabled($store->getWebsiteId());
            $catalogSyncEnabled = $this->helper->isCatalogSyncEnabled($store->getWebsiteId());

            if (!$enabled || !$catalogSyncEnabled) {
                continue;
            }

            $storeId = $store->getId();
            $this->appEmulation->startEnvironmentEmulation($storeId);

            $importType = 'Catalog_' . $store->getWebsite()->getCode() . '_' . $store->getCode();
            $products += $this->storeCatalogSyncer->syncByStore(
                $storeId,
                $store->getWebsiteId(),
                $limit,
                $importType
            );

            $this->appEmulation->stopEnvironmentEmulation();
        }

        /*
         * Sits outside of syncByStore by intention.
         * We grab products for sync from each store and put them all into an array, then we mark them all as imported.
         * This is to prevent products that need to be synced with multiple stores being marked as imported before they have been synced.
         */
        $this->catalogResource->setImportedByIds(array_keys($products));

        return count($products);
    }
}
