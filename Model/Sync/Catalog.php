<?php

namespace Dotdigitalgroup\Email\Model\Sync;

/**
 * Sync account TD for catalog.
 */
class Catalog implements SyncInterface
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
     * @var mixed
     */
    private $start;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory
     */
    public $catalogResourceFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory
     */
    private $catalogCollectionFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncFactory
     */
    private $catalogSyncFactory;

    /**
     * Catalog constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogResourceFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollectionFactory
     * @param \Dotdigitalgroup\Email\Model\Sync\Catalog\CatalogSyncFactory $catalogSyncFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $catalogResourceFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollectionFactory,
        Catalog\CatalogSyncFactory $catalogSyncFactory
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->catalogResourceFactory = $catalogResourceFactory;
        $this->catalogCollectionFactory = $catalogCollectionFactory;
        $this->catalogSyncFactory = $catalogSyncFactory;
    }

    /**
     * Catalog sync.
     *
     * @param \DateTime|null $from
     * @return array
     */
    public function sync(\DateTime $from = null)
    {
        $response = ['success' => true, 'message' => 'Done.'];

        if (!$this->shouldProceed()) {
            return $response;
        }

        $this->start = microtime(true);
        $limit = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT
        );

        $syncedProducts = [];
        $productsToProcess = $this->getProductsToProcess($limit);

        if (!$productsToProcess) {
            $message = 'Catalog sync skipped, no products to process.';
            $this->helper->log($message);
            $response['message'] = $message;
        } else {
            $syncedProducts = $this->syncCatalog($productsToProcess);

            $message = '----------- Catalog sync ----------- : ' .
                gmdate('H:i:s', microtime(true) - $this->start) .
                ', Total processed = ' . count($productsToProcess) . ', Total synced = ' . count($syncedProducts);
            $this->helper->log($message);
            $response['message'] = $message;
        }

        $this->catalogResourceFactory->create()
            ->setProcessedByIds($productsToProcess);

        $this->catalogResourceFactory->create()
            ->setImportedDateByIds(array_keys($syncedProducts));

        return $response;
    }

    /**
     * Sync product catalogs
     *
     * @param array $products
     *
     * @return array
     */
    public function syncCatalog($products)
    {
        try {
            //remove product with product id set and no product
            $this->catalogResourceFactory->create()
                ->removeOrphanProducts();

            return $this->catalogSyncFactory->create()->sync($products);

        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
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
            ->getProductsToProcess($limit);
    }

    /**
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
}
