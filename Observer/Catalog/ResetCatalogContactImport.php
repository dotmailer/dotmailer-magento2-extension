<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

/**
 * Catalog reset.
 */
class ResetCatalogContactImport implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Catalog
     */
    private $connectorCatalog;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $connectorContact;

    /**
     * ResetCatalogContactImport constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $connectorContact
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $connectorCatalog
     * @param \Magento\Framework\Registry $registry
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Contact $connectorContact,
        \Dotdigitalgroup\Email\Model\ResourceModel\Catalog $connectorCatalog,
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->connectorContact = $connectorContact;
        $this->connectorCatalog = $connectorCatalog;
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
            $this->resetConnectorCatalogFactoryIfRequired($observer);

            $this->resetAllContactsInConnectorCatalogFactoryIfRequired($observer);
        } catch (\Exception $e) {
            $this->helper->debug((string)$e, []);
        }

        return $this;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return null
     */
    private function resetConnectorCatalogFactoryIfRequired(\Magento\Framework\Event\Observer $observer)
    {
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
                        $this->connectorCatalog->reset();
                    }
                    $this->registry->unregister('core_config_data_save_after_done'); // additional measure
                    $this->registry->register(
                        'core_config_data_save_after_done',
                        true
                    );
                }
            }
        }
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return null
     */
    private function resetAllContactsInConnectorCatalogFactoryIfRequired(\Magento\Framework\Event\Observer $observer)
    {
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
                        $this->connectorContact->resetAllContacts();
                    }
                    $this->registry->unregister('core_config_data_save_after_done_status'); // additional measure
                    $this->registry->register(
                        'core_config_data_save_after_done_status',
                        true
                    );
                }
            }
        }
    }
}
