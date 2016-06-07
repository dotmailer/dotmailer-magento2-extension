<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

class RegisterCatalogValuesOrderStatuses
    implements \Magento\Framework\Event\ObserverInterface
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
     * RegisterCatalogValuesOrderStatuses constructor.
     *
     * @param \Magento\Framework\Registry        $registry
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Dotdigitalgroup\Email\Helper\Data $data
    ) {
        $this->_helper = $data;
        $this->_registry = $registry;
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
        //register catalog values
        if (!$this->_registry->registry('core_config_data_save_before')) {
            if ($groups = $observer->getEvent()->getConfigData()->getGroups()) {
                if (isset($groups['catalog_sync']['fields']['catalog_values']['value'])) {
                    $value
                        = $groups['catalog_sync']['fields']['catalog_values']['value'];
                    $this->_registry->register('core_config_data_save_before',
                        $value);
                }
            }
        }
        //register order statuses
        if (!$this->_registry->registry('core_config_data_save_before_status')) {
            if ($groups = $observer->getEvent()->getConfigData()->getGroups()) {
                if (isset($groups['data_fields']['fields']['order_statuses']['value'])) {
                    $value
                        = $groups['data_fields']['fields']['order_statuses']['value'];
                    $this->_registry->register('core_config_data_save_before_status',
                        $value);
                }
            }
        }

        return $this;
    }
}
