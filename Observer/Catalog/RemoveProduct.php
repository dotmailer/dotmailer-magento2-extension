<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

/**
 * Product that was deleted to be removed.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RemoveProduct implements \Magento\Framework\Event\ObserverInterface
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
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\CatalogFactory
     */
    private $catalogFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * RemoveProduct constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->importerFactory   = $importerFactory;
        $this->helper            = $data;
        $this->scopeConfig       = $scopeConfig;
        $this->catalogFactory    = $catalogFactory;
        $this->storeManager      = $storeManagerInterface;
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $object = $observer->getEvent()->getDataObject();
            $productId = $object->getId();
            $emailCatalog = $this->catalogFactory->create();
            if ($item = $emailCatalog->loadProductById($productId)) {
                // if ever imported, delete from account
                if ($item->getLastImportedAt()) {
                    $this->deleteFromAccount($productId);
                }
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }

    /**
     * Delete piece of transactional data by key.
     *
     * @param int $key
     * @return void
     */
    protected function deleteFromAccount($key): void
    {
        $apiEnabled = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED
        );
        $catalogEnabled = $this->helper->isCatalogSyncEnabled();
        if ($apiEnabled && $catalogEnabled) {
            $scope = $this->scopeConfig->getValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES
            );
            if ($scope == 1) {
                //register in queue with importer
                $this->importerFactory->create()
                    ->registerQueue(
                        'Catalog_Default',
                        [$key],
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                        \Magento\Store\Model\Store::DEFAULT_STORE_ID
                    );
            }
            if ($scope == 2) {
                $stores = $this->storeManager->getStores();
                foreach ($stores as $store) {
                    /** @var \Magento\Store\Model\Store $store */
                    $websiteCode = $store->getWebsite()->getCode();
                    $storeCode = $store->getCode();

                    //register in queue with importer
                    $this->importerFactory->create()
                        ->registerQueue(
                            'Catalog_' . $websiteCode . '_' . $storeCode,
                            [$key],
                            \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                            $store->getWebsite()->getId()
                        );
                }
            }
        }
    }
}
