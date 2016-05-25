<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

class RemoveProduct implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\CatalogFactory
     */
    protected $_catalogFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Resource\Catalog\CollectionFactory
     */
    protected $_catalogCollection;
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    protected $_importerFactory;

    /**
     * RemoveProduct constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory                    $importerFactory
     * @param \Dotdigitalgroup\Email\Model\CatalogFactory                     $catalogFactory
     * @param \Dotdigitalgroup\Email\Model\Resource\Catalog\CollectionFactory $catalogCollectionFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                              $data
     * @param \Magento\Framework\App\Config\ScopeConfigInterface              $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface                      $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory,
        \Dotdigitalgroup\Email\Model\Resource\Catalog\CollectionFactory $catalogCollectionFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_importerFactory = $importerFactory;
        $this->_helper = $data;
        $this->_scopeConfig = $scopeConfig;
        $this->_catalogFactory = $catalogFactory;
        $this->_catalogCollection = $catalogCollectionFactory;
        $this->_storeManager = $storeManagerInterface;
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
            if ($item = $this->_loadProduct($productId)) {
                //if imported delete from account
                if ($item->getImported()) {
                    $this->_deleteFromAccount($productId);
                }
                //delete from table
                $item->delete();
            }
        } catch (\Exception $e) {
            $this->_helper->debug((string) $e, []);
        }
    }

    /**
     * Load product. return item otherwise create item.
     *
     * @param int $productId
     *
     * @return bool
     */
    private function _loadProduct($productId)
    {
        $collection = $this->_catalogCollection->create()
            ->addFieldToFilter('product_id', $productId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        } else {
            $this->_catalogFactory->create()
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
    private function _deleteFromAccount($key)
    {
        $apiEnabled = $this->_scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED);
        $catalogEnabled = $this->_helper->isCatalogSyncEnabled();
        if ($apiEnabled && $catalogEnabled) {
            $scope = $this->_scopeConfig->getValue(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES);
            if ($scope == 1) {
                //register in queue with importer
                $this->_importerFactory->create()
                    ->registerQueue(
                        'Catalog_Default',
                        [$key],
                        \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                        \Magento\Store\Model\Store::DEFAULT_STORE_ID
                    );
            }
            if ($scope == 2) {
                $stores = $this->_storeManager->getStores();
                foreach ($stores as $store) {
                    $websiteCode = $store->getWebsite()->getCode();
                    $storeCode = $store->getCode();

                    //register in queue with importer
                    $this->_importerFactory->create()
                        ->registerQueue(
                            'Catalog_'.$websiteCode.'_'.$storeCode,
                            [$key],
                            \Dotdigitalgroup\Email\Model\Importer::MODE_SINGLE_DELETE,
                            $store->getWebsite()->getId()
                        );
                }
            }
        }
    }
}
