<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

class ResetCatalogContactImport implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory
     */
    public $connectorCatalogFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory
     */
    public $connectorContactFactory;

    /**
     * ResetCatalogContactImport constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $connectorContactFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $connectorCatalogFactory
     * @param \Magento\Framework\Registry                               $registry
     * @param \Dotdigitalgroup\Email\Helper\Data                        $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory $connectorContactFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory $connectorCatalogFactory,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->connectorContactFactory = $connectorContactFactory;
        $this->connectorCatalogFactory = $connectorCatalogFactory;
        $this->helper                  = $data;
        $this->registry                = $registry;
    }

    /**
     * Execute method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            if (!$this->registry->registry('core_config_data_save_after_done')) {
                if ($groups = $observer->getEvent()->getConfigData()
                    ->getGroups()
                ) {
                    if (isset($groups['catalog_sync']['fields']['catalog_values']['value'])) {
                        $configAfter
                            = $groups['catalog_sync']['fields']['catalog_values']['value'];
                        $configBefore
                            = $this->registry->registry('core_config_data_save_before');
                        if ($configAfter != $configBefore) {
                            //reset catalog to re-import
                            $this->connectorCatalogFactory->create()
                                ->reset();
                        }
                        $this->registry->register(
                            'core_config_data_save_after_done',
                            true
                        );
                    }
                }
            }

            if (!$this->registry->registry('core_config_data_save_after_done_status')) {
                if ($groups = $observer->getEvent()->getConfigData()
                    ->getGroups()
                ) {
                    if (isset($groups['data_fields']['fields']['order_statuses']['value'])) {
                        $configAfter
                            = $groups['data_fields']['fields']['order_statuses']['value'];
                        $configBefore
                            = $this->registry->registry('core_config_data_save_before_status');
                        if ($configAfter != $configBefore) {
                            //reset all contacts
                            $this->connectorContactFactory->create()
                                ->resetAllContacts();
                        }
                        $this->registry->register(
                            'core_config_data_save_after_done_status',
                            true
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }
}
