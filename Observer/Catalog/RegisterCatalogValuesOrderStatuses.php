<?php

namespace Dotdigitalgroup\Email\Observer\Catalog;

class RegisterCatalogValuesOrderStatuses implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * RegisterCatalogValuesOrderStatuses constructor.
     *
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
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
        if (!$this->registry->registry('core_config_data_save_before')) {
            if ($groups = $observer->getEvent()->getConfigData()->getGroups()) {
                if (isset($groups['catalog_sync']['fields']['catalog_values']['value'])) {
                    $value
                        = $groups['catalog_sync']['fields']['catalog_values']['value'];
                    $this->registry->unregister('core_config_data_save_before'); // additional measure
                    $this->registry->register(
                        'core_config_data_save_before',
                        $value
                    );
                }
            }
        }
        //register order statuses
        if (!$this->registry->registry('core_config_data_save_before_status')) {
            if ($groups = $observer->getEvent()->getConfigData()->getGroups()) {
                if (isset($groups['data_fields']['fields']['order_statuses']['value'])) {
                    $value
                        = $groups['data_fields']['fields']['order_statuses']['value'];
                    $this->registry->unregister('core_config_data_save_before_status'); // additional measure
                    $this->registry->register(
                        'core_config_data_save_before_status',
                        $value
                    );
                }
            }
        }

        return $this;
    }
}
