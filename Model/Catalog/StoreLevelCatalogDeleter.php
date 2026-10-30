<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Catalog;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\CatalogFactory;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Queues deletion of catalog records from store-level collections.
 */
class StoreLevelCatalogDeleter
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CatalogFactory
     */
    private $catalogFactory;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param CatalogFactory $catalogFactory
     * @param ImporterFactory $importerFactory
     */
    public function __construct(
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        CatalogFactory $catalogFactory,
        ImporterFactory $importerFactory
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->catalogFactory = $catalogFactory;
        $this->importerFactory = $importerFactory;
    }

    /**
     * Queue a delete for each store of the given websites (all websites if null).
     *
     * Only products that have been imported previously are queued.
     *
     * @param int $productId
     * @param array|null $websiteIds
     * @return void
     */
    public function execute(int $productId, ?array $websiteIds = null): void
    {
        if (!$this->isStoreLevelSyncActive() || !$this->hasBeenImported($productId)) {
            return;
        }

        foreach ($this->storeManager->getStores() as $store) {
            $websiteId = (int) $store->getWebsiteId();
            if ($websiteIds !== null && !in_array($websiteId, array_map('intval', $websiteIds), true)) {
                continue;
            }

            $website = $this->storeManager->getWebsite($websiteId);

            $this->importerFactory->create()->registerQueue(
                'Catalog_' . $website->getCode() . '_' . $store->getCode(),
                [$productId],
                Importer::MODE_SINGLE_DELETE,
                $websiteId
            );
        }
    }

    /**
     * Whether catalog sync is enabled and set to store level.
     *
     * @return bool
     */
    private function isStoreLevelSyncActive(): bool
    {
        return $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_API_ENABLED)
            && $this->helper->isCatalogSyncEnabled()
            && (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES)
                === CatalogSyncFactory::SYNC_CATALOG_STORE_LEVEL;
    }

    /**
     * Check email_catalog table to see if the product has been imported before.
     *
     * @param int $productId
     * @return bool
     */
    private function hasBeenImported(int $productId): bool
    {
        $item = $this->catalogFactory->create()->loadProductById($productId);

        return (bool) $item->getLastImportedAt();
    }
}
