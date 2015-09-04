<?php

namespace Dotdgitalgroup\Email\Model\Catalog;

class Observer
{
	protected $_helper;
	protected $_registry;
	protected $_logger;
	protected $_scopeConfig;
	protected $_storeManager;
	protected $_objectManager;

	public function __construct(
		\Magento\Framework\Registry $registry,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Psr\Log\LoggerInterface $loggerInterface,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
		$this->_helper = $data;
		$this->_scopeConfig = $scopeConfig;
		$this->_logger = $loggerInterface;
		$this->_storeManager = $storeManagerInterface;
		$this->_registry = $registry;
		$this->_objectManager = $objectManagerInterface;

	}


	/**
	 * product save after event processor
	 *
	 */
	public function handleProductSaveAfter($observer)
	{
		try{
			$object = $observer->getEvent()->getDataObject();
			$productId = $object->getId();
			if($item = $this->_loadProduct($productId)){
				if($item->getImported())
					$item->setModified(1)->save();
			}
		}catch (\Exception $e){
		}
	}

	/**
	 * product delete after event processor.
	 */
	public function handleProductDeleteAfter($observer)
	{
		try{
			$object = $observer->getEvent()->getDataObject();
			$productId = $object->getId();
			if($item = $this->_loadProduct($productId)){
				//if imported delete from account
				if($item->getImported()){
					$this->_deleteFromAccount($productId);
				}
				//delete from table
				$item->delete();
			}
		}catch (\Exception $e){
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
		$collection = $this->getCollection()
           ->addFieldToFilter('product_id', $productId)
           ->setPageSize(1);
var_dump($collection->getData());die;
		if ($collection->getSize()) {
			return $collection->getFirstItem();
		} else {
			$this->setProductId($productId)
				->save();
		}
		return false;
	}

	/**
	 * delete piece of transactional data by key
	 *
	 * @param $key
	 */
	private function _deleteFromAccount($key)
	{
		$apiEnabled = $this->_scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED );
		$catalogEnabled = $this->_helper->getCatalogSyncEnabled();
		if($apiEnabled && $catalogEnabled){
			$scope = $this->_scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES);
			if($scope == 1){
				//register in queue with importer
				$this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')->registerQueue(
					\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_CATALOG,
					array($key),
					\Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE_DELETE,
					\Magento\Store\Model\Store::DEFAULT_STORE_ID
				);
			}
			if($scope == 2){
				$stores = $this->_storeManager->getStores();
				foreach($stores as $store){
					$websiteCode = $store->getWebsite()->getCode();
					$storeCode = $store->getCode();

					//register in queue with importer
					$this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')->registerQueue(
						'Catalog_' . $websiteCode . '_' . $storeCode,
						array($key),
						\Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE_DELETE,
						$store->getWebsite()->getId()
					);
				}
			}
		}
	}
	/**
	 * core config data save before event
	 *
	 * @return $this
	 */
	public function handleConfigSaveBefore( $observer)
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

	/**
	 * core config data save after event
	 *
	 * @return $this
	 */
	public function handleConfigSaveAfter($observer)
	{
		try{
			if(! $this->_registry->registry('core_config_data_save_after_done')){
				if($groups = $observer->getEvent()->getConfigData()->getGroups()){
					if(isset($groups['catalog_sync']['fields']['catalog_values']['value'])){
						$configAfter = $groups['catalog_sync']['fields']['catalog_values']['value'];
						$configBefore = $this->_registry->registry('core_config_data_save_before');
						if($configAfter != $configBefore){
							//reset catalog to re-import
							$this->_objectManager->create('Dotdigitalgroup\Email\Model\Resource\Catalog')->reset();
						}
						$this->_registry->register('core_config_data_save_after_done', true);
					}
				}
			}

			if (! $this->_registry->registry('core_config_data_save_after_done_status')) {
				if ($groups = $observer->getEvent()->getConfigData()->getGroups()) {
					if (isset($groups['data_fields']['fields']['order_statuses']['value'])) {
						$configAfter = $groups['data_fields']['fields']['order_statuses']['value'];
						$configBefore = $this->_registry->registry('core_config_data_save_before_status');
						if ($configAfter != $configBefore) {
							//reset all contacts
							$this->_objectManager->create('Dotdigitalgroup\Email\Model\Resource\Contact')->resetAllContacts();
						}
						$this->_registry->register('core_config_data_save_after_done_status', true);
					}
				}
			}
		}catch (\Exception $e){
		}
		return $this;
	}

}