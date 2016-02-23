<?php


namespace Dotdigitalgroup\Email\Observer\Catalog;

class ResetCatalogContactImport
    implements \Magento\Framework\Event\ObserverInterface
{

    protected $_helper;
    protected $_registry;
    protected $_logger;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_catalogFactory;
    protected $_catalogCollection;
    protected $_proccessorFactory;
    protected $_connectorCatalogFactory;
    protected $_connectorContactFactory;

    /**
     * ResetCatalogContactImport constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Resource\ContactFactory            $connectorContactFactory
     * @param \Dotdigitalgroup\Email\Model\Resource\CatalogFactory            $connectorCatalogFactory
     * @param \Dotdigitalgroup\Email\Model\ProccessorFactory                  $proccessorFactory
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
        \Dotdigitalgroup\Email\Model\ProccessorFactory $proccessorFactory,
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
        $this->_proccessorFactory       = $proccessorFactory;
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
            if ( ! $this->_registry->registry('core_config_data_save_after_done')) {
                if ($groups = $observer->getEvent()->getConfigData()
                    ->getGroups()
                ) {
                    if (isset($groups['catalog_sync']['fields']['catalog_values']['value'])) {
                        $configAfter
                            = $groups['catalog_sync']['fields']['catalog_values']['value'];
                        $configBefore
                            = $this->_registry->registry('core_config_data_save_before');
                        if ($configAfter != $configBefore) {
                            //reset catalog to re-import
                            $this->_connectorCatalogFactory->create()
                                ->reset();
                        }
                        $this->_registry->register('core_config_data_save_after_done',
                            true);
                    }
                }
            }

            if ( ! $this->_registry->registry('core_config_data_save_after_done_status')) {
                if ($groups = $observer->getEvent()->getConfigData()
                    ->getGroups()
                ) {
                    if (isset($groups['data_fields']['fields']['order_statuses']['value'])) {
                        $configAfter
                            = $groups['data_fields']['fields']['order_statuses']['value'];
                        $configBefore
                            = $this->_registry->registry('core_config_data_save_before_status');
                        if ($configAfter != $configBefore) {
                            //reset all contacts
                            $this->_connectorContactFactory->create()
                                ->resetAllContacts();
                        }
                        $this->_registry->register('core_config_data_save_after_done_status',
                            true);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_helper->debug((string)$e, array());
        }

        return $this;
    }
}
