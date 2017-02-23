<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

class RemoveProduct implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\CatalogFactory
     */
    public $catalogFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory
     */
    public $catalogCollection;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    public $importerFactory;

    /**
     * RemoveProduct constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory                         $importerFactory
     * @param \Dotdigitalgroup\Email\Model\CatalogFactory                          $catalogFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollectionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                                   $data
     * @param \Magento\Framework\App\Config\ScopeConfigInterface                   $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface                           $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory $catalogCollectionFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->importerFactory   = $importerFactory;
        $this->helper            = $data;
        $this->scopeConfig       = $scopeConfig;
        $this->catalogFactory    = $catalogFactory;
        $this->catalogCollection = $catalogCollectionFactory;
        $this->storeManager      = $storeManagerInterface;
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $object = $observer->getEvent()->getDataObject();
            $productId = $object->getId();
            if ($item = $this->loadProduct($productId)) {
                //if imported delete from account
                if ($item->getImported()) {
                    $this->deleteFromAccount($productId);
                }
                //delete from table
                $item->delete();
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }
    }

    /**
     * Load product. return item otherwise create item.
     *
     * @param int $productId
     *
     * @return bool
     */
    protected function loadProduct($productId)
    {
        $collection = $this->catalogCollection->create()
            ->addFieldToFilter('product_id', $productId)
            ->setPageSize(1);

        //@codingStandardsIgnoreStart
        if ($collection->getSize()) {
            return $collection->getFirstItem();
            //@codingStandardsIgnoreEnd
        } else {
            $this->catalogFactory->create()
                ->setProductId($productId)
                ->save();
        }

        return false;
    }

    /**
     * Delete piece of transactional data by key.
     *
     * @param int $key
     */
    protected function deleteFromAccount($key)
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
