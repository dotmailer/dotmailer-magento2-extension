<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use DateTime;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncFactory;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;

/**
 * Sync account TD for catalog.
 */
class Catalog extends DataObject implements SyncInterface
{
    /**
     * @cont string
     */
    public const DEFAULT_CATALOG_NAME = 'Catalog_Default';

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CatalogFactory
     */
    public $catalogResourceFactory;

    /**
     * @var CollectionFactory
     */
    private $catalogCollectionFactory;

    /**
     * @var CatalogSyncFactory
     */
    private $catalogSyncFactory;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * Catalog constructor.
     *
     * @param Data $helper
     * @param ScopeConfigInterface $scopeConfig
     * @param CatalogFactory $catalogResourceFactory
     * @param CollectionFactory $catalogCollectionFactory
     * @param CatalogSyncFactory $catalogSyncFactory
     * @param ImporterFactory $importerFactory
     * @param array $data
     */
    public function __construct(
        Data $helper,
        ScopeConfigInterface $scopeConfig,
        CatalogFactory $catalogResourceFactory,
        CollectionFactory $catalogCollectionFactory,
        Catalog\CatalogSyncFactory $catalogSyncFactory,
        ImporterFactory $importerFactory,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->catalogResourceFactory = $catalogResourceFactory;
        $this->catalogCollectionFactory = $catalogCollectionFactory;
        $this->catalogSyncFactory = $catalogSyncFactory;
        $this->importerFactory = $importerFactory;
        parent::__construct($data);
    }

    /**
     * Catalog sync.
     *
     * @param DateTime|null $from
     * @return array
     */
    public function sync(DateTime $from = null)
    {
        $response = ['success' => true, 'message' => 'Done.'];

        if (!$this->shouldProceed()) {
            return $response;
        }

        $start = microtime(true);
        $limit = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );

        //remove product with product id set and no product
        $this->catalogResourceFactory->create()
            ->removeOrphanProducts();

        $megaBatch = [];
        $productsProcessedCount = 0;
        $megaBatchCount = 0;
        $totalProductsSyncedCount = 0;
        $loopStart = true;

        $breakValue = $this->isRunFromDeveloperButton() ? $limit : $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE
        );

        $megaBatchSize = $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CATALOG);

        do {
            $productsToProcess = $this->getProductsToProcess($limit);

            if (!$productsToProcess) {
                break;
            }

            if ($loopStart) {
                $this->helper->log('----------- Catalog sync ----------- : Start batching');
                $loopStart = false;
            }

            $megaBatch = $this->mergeBatch(
                $megaBatch,
                $batch = $this->syncCatalog($productsToProcess)
            );

            $productsProcessedCount += count($productsToProcess);
            $megaBatchCount += $batchCount = $this->getBatchProductsCount($batch);
            $totalProductsSyncedCount += $batchCount;

            $this->catalogResourceFactory->create()
                ->setProcessedByIds($productsToProcess);

            $this->helper->log(sprintf('Catalog sync: %s products processed.', count($productsToProcess)));

            if ($megaBatchCount >= $megaBatchSize) {
                $this->addToImportQueue($megaBatch);
                $megaBatchCount = 0;
                $megaBatch = [];
            }

        } while (!$breakValue || $totalProductsSyncedCount < $breakValue);

        if (!empty($megaBatch)) {
            //Add the rest of the products (if any) to the importer
            $this->addToImportQueue($megaBatch);
        }

        $message = '----------- Catalog sync ----------- : ' .
            gmdate('H:i:s', (int) (microtime(true) - $start)) .
            ', Total processed = ' . $productsProcessedCount . ', Total synced = ' . $totalProductsSyncedCount;
        $this->helper->log($message);
        $response['message'] = $message;
        return $response;
    }

    /**
     * Get catalogue name for sync
     *
     * @param StoreInterface $store
     * @param int|null $catalogSyncLevel
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreCatalogName(
        StoreInterface $store,
        ?int $catalogSyncLevel = null
    ):string {
        $syncLevel = $catalogSyncLevel ?? $this->getCatalogSyncLevel();
        if ($syncLevel == CatalogSyncFactory::SYNC_CATALOG_DEFAULT_LEVEL) {
            return self::DEFAULT_CATALOG_NAME;
        };
        /** @var Store $store */
        $website = $store->getWebsite();
        return join('_', [
            'Catalog',
            $website->getCode(),
            $store->getCode()
        ]);
    }

    /**
     * Get catalog Sync level
     *
     * @return int
     */
    public function getCatalogSyncLevel():int
    {
        return $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES);
    }

    /**
     * Fetch product ids.
     *
     * @param array $syncedProducts
     * @return array
     */
    private function getSyncedProductIds($syncedProducts)
    {
        $productIds = [];
        foreach ($syncedProducts as $batch) {
            $productIds += array_keys($batch['products']);
        }

        return array_unique($productIds);
    }

    /**
     * Sync product catalogs
     *
     * @param array $products
     *
     * @return array
     */
    private function syncCatalog($products)
    {
        try {
            return $this->catalogSyncFactory->create()
                ->sync($products);

        } catch (Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return [];
    }

    /**
     * Get products to process.
     *
     * @param int $limit
     *
     * @return array
     */
    private function getProductsToProcess($limit)
    {
        return $this->catalogCollectionFactory->create()
            ->getUnprocessedProducts($limit);
    }

    /**
     * Decides if sync should proceed.
     *
     * @return bool
     */
    private function shouldProceed()
    {
        // check default level
        $apiEnabled = $this->helper->isEnabled();
        $catalogSyncEnabled = $this->helper->isCatalogSyncEnabled();

        if ($apiEnabled && $catalogSyncEnabled) {
            return true;
        }

        // not enabled at default, check each website, exiting as soon as we find an enabled website
        $websites = $this->helper->getWebsites();
        foreach ($websites as $website) {

            $apiEnabled = $this->helper->isEnabled($website);
            $catalogSyncEnabled = $this->helper->isCatalogSyncEnabled($website);

            if ($apiEnabled && $catalogSyncEnabled) {
                return true;
            }
        }
        return false;
    }

    /**
     * Creates the catalog mega batch.
     *
     * @param array $megaBatch
     * @param array $batch
     * @return array
     */
    private function mergeBatch(array $megaBatch, array $batch)
    {
        foreach ($batch as $catalogName => $set) {
            if (array_key_exists($catalogName, $megaBatch)) {
                if (isset($set['products'])) {
                    $megaBatch[$catalogName]['products'] += $set['products'];
                }
            } else {
                $megaBatch += [$catalogName => $set];
            }
        }

        return $megaBatch;
    }

    /**
     * Register catalog to importer.
     *
     * @param array $catalogs
     */
    private function addToImportQueue(array $catalogs)
    {
        foreach ($catalogs as $catalogName => $batch) {
            if (!$batch['products']) {
                continue;
            }

            $importer = $this->importerFactory->create();
            /** @var Importer $importer */
            $success = $importer
                ->registerQueue(
                    $catalogName,
                    $batch['products'],
                    Importer::MODE_BULK,
                    $batch['websiteId']
                );

            if (!$success) {
                $pid = implode(",", array_keys($batch['products']));
                $msg = "Failed to register with IMPORTER."
                    . "Type(Catalog) / Scope(Bulk) / Website({$batch['websiteId']}) / Product Ids($pid)";
                $this->helper->log($msg);
            } else {
                $this->helper->log(
                    sprintf(
                        'Catalog sync [%s]: %s products batched for importer.',
                        $catalogName,
                        count($batch['products'])
                    )
                );
            }
        }

        $this->catalogResourceFactory->create()
            ->setImportedDateByIds(
                $this->getSyncedProductIds($catalogs)
            );
    }

    /**
     * Product counter.
     *
     * @param array $batch
     * @return int
     */
    private function getBatchProductsCount(array $batch)
    {
        $productsToSync = 0;

        foreach ($batch as $importerItems) {
            $productsToSync += count($importerItems['products']);
        }

        return $productsToSync;
    }

    /**
     * Determines whether the sync was triggered from Configuration > Dotdigital > Developer > Sync Settings.
     *
     * @return bool
     */
    private function isRunFromDeveloperButton()
    {
        return $this->_getData('web') ? true : false;
    }
}
