<?php


namespace Dotdigitalgroup\Email\Observer\Catalog;

class ReimportProduct implements \Magento\Framework\Event\ObserverInterface
{

    protected $_helper;
    protected $_registry;
    protected $_logger;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_catalogFactory;
    protected $_catalogCollection;
    protected $_connectorCatalogFactory;
    protected $_connectorContactFactory;

    /**
     * ReimportProduct constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Resource\ContactFactory            $connectorContactFactory
     * @param \Dotdigitalgroup\Email\Model\Resource\CatalogFactory            $connectorCatalogFactory
     * @param \Dotdigitalgroup\Email\Model\CatalogFactory                     $catalogFactory
     * @param \Dotdigitalgroup\Email\Model\Resource\Catalog\CollectionFactory $catalogCollectionFactory
     * @param \Magento\Framework\Registry                                     $registry
     * @param \Dotdigitalgroup\Email\Helper\Data                              $data
     * @param \Psr\Log\LoggerInterface                                        $loggerInterface
     * @param \Magento\Framework\App\Config\ScopeConfigInterface              $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface                      $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Resource\ContactFactory $connectorContactFactory,
        \Dotdigitalgroup\Email\Model\Resource\CatalogFactory $connectorCatalogFactory,
        \Dotdigitalgroup\Email\Model\CatalogFactory $catalogFactory,
        \Dotdigitalgroup\Email\Model\Resource\Catalog\CollectionFactory $catalogCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_connectorContactFactory = $connectorContactFactory;
        $this->_connectorCatalogFactory = $connectorCatalogFactory;
        $this->_helper                  = $data;
        $this->_registry                = $registry;
        $this->_logger                  = $loggerInterface;
        $this->_scopeConfig             = $scopeConfig;
        $this->_catalogFactory          = $catalogFactory;
        $this->_catalogCollection       = $catalogCollectionFactory;
        $this->_storeManager            = $storeManagerInterface;
    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $object    = $observer->getEvent()->getDataObject();
            $productId = $object->getId();

            if ($item = $this->_loadProduct($productId)) {
                if ($item->getImported()) {
                    $item->setModified(1)
                        ->save();
                }
            }
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, array());
        }
    }

    /**
     * load product. return item otherwise create item.
     *
     * @param $productId
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

}
