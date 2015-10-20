<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dotdigitalgroup\Email\Observer\Catalog;


class RegisterCatalgoValuesOrderStatuses implements \Magento\Framework\Event\ObserverInterface
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
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
		$this->_connectorContactFactory = $connectorContactFactory;
		$this->_connectorCatalogFactory = $connectorCatalogFactory;
		$this->_proccessorFactory = $proccessorFactory;
		$this->_helper = $data;
		$this->_registry = $registry;
		$this->_logger = $loggerInterface;
		$this->_scopeConfig = $scopeConfig;
		$this->_catalogFactory = $catalogFactory;
		$this->_catalogCollection = $catalogCollectionFactory;
		$this->_storeManager = $storeManagerInterface;
	}


	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		//register catalog values
		if (! $this->_registry->registry('core_config_data_save_before')){
			if($groups = $observer->getEvent()->getConfigData()->getGroups()){
				if(isset($groups['catalog_sync']['fields']['catalog_values']['value'])){
					$value = $groups['catalog_sync']['fields']['catalog_values']['value'];
					$this->_registry->register('core_config_data_save_before', $value);
				}
			}
		}
		//register order statuses
		if (! $this->_registry->registry('core_config_data_save_before_status')) {
			if ($groups = $observer->getEvent()->getConfigData()->getGroups()) {
				if (isset($groups['data_fields']['fields']['order_statuses']['value'])) {
					$value = $groups['data_fields']['fields']['order_statuses']['value'];
					$this->_registry->register('core_config_data_save_before_status', $value);
				}
			}
		}

		return $this;
	}
}
