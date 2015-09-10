<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Catalog
{

	protected $_helper;
	protected $_resource;
	protected $_scopeConfig;
	protected $_objectManager;
	protected $_productFactory;

	private $_start;
	private $_countProducts = 0;
	private $_productIds;

	public function __construct(
		\Magento\Framework\App\Resource $resource,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Catalog\Model\ProductFactory $productFactory,
		\Magento\Framework\ObjectManagerInterface $objectManager
	)
	{
		$this->_helper = $helper;
		$this->_resource = $resource;
		$this->_scopeConfig = $scopeConfig;
		$this->_objectManager = $objectManager;
		$this->_productFactory = $productFactory->create();
	}
	/**
	 *
	 * catalog sync
	 *
	 * @return array
	 */
	public function sync()
	{
		$response = array('success' => true, 'message' => 'Done.');
		$this->_start = microtime(true);
		$proccessorModel = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor');


		//resource allocation
		$this->_helper->allowResourceFullExecution();
		$enabled = $this->_helper->isEnabled();
		$catalogSyncEnabled = $this->_helper->getCatalogSyncEnabled();
		//api and catalog sync enabled
		if ($enabled && $catalogSyncEnabled) {
			$this->_helper->log('---------- Start catalog sync ----------');

			//remove product with product id set and no product
			$write = $this->_resource->getConnection('core_write');
			$catalogTable = $this->_resource->getTableName('email_catalog');
			$select = $write->select();
			$select->reset()
			       ->from(
				       array('c' => $catalogTable),
				       array('c.product_id')
			       )
			       ->joinLeft(
				       array('e' => $this->_resource->getTableName('catalog_product_entity')),
				       "c.product_id = e.entity_id"
			       )
			       ->where('e.entity_id is NULL');
			//delete sql statement
			$deleteSql = $select->deleteFromSelect('c');
			//run query
			$write->query($deleteSql);
			$scope = $this->_scopeConfig->getValue(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VALUES);

			//if only to pull default value
			if($scope == 1){
				$products = $this->_exportCatalog(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
				if ($products) {
					//register in queue with importer
					$check = $proccessorModel->registerQueue(
						\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_CATALOG,
						$products,
						\Dotdigitalgroup\Email\Model\Proccessor::MODE_BULK,
						\Magento\Store\Model\Store::DEFAULT_STORE_ID
					);

					//set imported
					if ($check)
						$this->_setImported($this->_productIds);

					//set number of product imported
					$this->_countProducts += count($products);
				}
				//using single api
				$this->_exportInSingle(\Magento\Store\Model\Store::DEFAULT_STORE_ID, 'Catalog_Default', \Magento\Store\Model\Store::DEFAULT_STORE_ID);
				//if to pull store values. will be pulled for each store
			}elseif($scope == 2){
				$stores = $this->_helper->getStores();
				foreach($stores as $store){
					$websiteCode = $store->getWebsite()->getCode();
					$storeCode = $store->getCode();
					$products = $this->_exportCatalog($store);
					if($products){
						$proccessorModel = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor');
						//register in queue with importer
						$check = $proccessorModel->registerQueue(
							'Catalog_' . $websiteCode . '_' . $storeCode,
							$products,
							\Dotdigitalgroup\Email\Model\Proccessor::MODE_BULK,
							$store->getWebsite()->getId()
						);
						//set imported
						if ($check)
							$this->_setImported($this->_productIds);

						//set number of product imported
						$this->_countProducts += count($products);
					}
					//using single api
					$this->_exportInSingle($store, 'Catalog_' . $websiteCode . '_' . $storeCode, $store->getWebsite()->getId());
				}
			}
		}

		if ($this->_countProducts) {
			$message = 'Total time for sync : ' . gmdate( "H:i:s", microtime( true ) - $this->_start ) . ', Total synced = ' . $this->_countProducts;
			$this->_helper->log( $message );
			$response['message'] = $message;
		}
		return $response;
	}


	/**
	 * export catalog
	 *
	 * @param $store
	 * @return array|bool
	 */
	private function _exportCatalog($store)
	{
		$products = $this->_getProductsToExport($store);
		if($products){
			$this->_productIds = $products->getColumnValues('entity_id');
			$connectorProducts = array();
			foreach($products as $product){

				$connectorProduct = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Connector\Product', ['product' => $product]);
				$connectorProducts[] = $connectorProduct;
			}
			return $connectorProducts;
		}
		return false;
	}

	/**
	 * export in single
	 *
	 * @param $store
	 * @param $collectionName
	 * @param $websiteId
	 */
	private function _exportInSingle($store, $collectionName, $websiteId)
	{
		$this->_productIds = array();

		$products = $this->_getProductsToExport($store, true);
		if($products){
			foreach($products as $product){
				$connectorProduct = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Connector\Product', array($product));
				$this->_helper->log('---------- Start catalog single sync ----------');

				//register in queue with importer
				$check = $this->_objectManager->create('Dotdigitalgroup\Email\Mode\Proccessor')->registerQueue(
					$collectionName,
					$connectorProduct,
					\Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE,
					$websiteId
				);

				if ($check) {
					$this->_productIds[] = $product->getId();
				}
			}

			if(!empty($this->_productIds)){
				$this->_setImported($this->_productIds, true);
				$this->_countProducts += count($this->_productIds);
			}
		}
	}

	/**
	 * @param $store
	 * @param bool|false $modified
	 *
	 * @return bool
	 */
	private function _getProductsToExport($store, $modified = false)
	{
		$limit = $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT);
		$connectorCollection = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Catalog')->getCollection();

		if($modified)
			$connectorCollection->addFieldToFilter('modified', array('eq' => '1'));
		else
			$connectorCollection->addFieldToFilter('imported', array('null' => 'true'));

		$connectorCollection->setPageSize($limit);

		if($connectorCollection->getSize()) {
			$product_ids = $connectorCollection->getColumnValues('product_id');

			$productCollection = $this->_productFactory->getCollection();

			$productCollection
				->addAttributeToSelect('*')
				->addStoreFilter($store)
				->addAttributeToFilter('entity_id', array('in' => $product_ids));

			//visibility filter
			if($visibility = $this->_helper->getWebsiteConfig(
				\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY)){
				$visibility = explode(',', $visibility);
				$productCollection->addAttributeToFilter('visibility', array('in' => $visibility));
			}

			//type filter
			if($type = $this->_helper->getWebsiteConfig(
				\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE)){
				$type = explode(',', $type);
				$productCollection->addAttributeToFilter('type_id', array('in' => $type));
			}

			$productCollection
				->addWebsiteNamesToResult()
				->addFinalPrice()
				->addCategoryIds()
				->addOptionsToResult();

			return $productCollection;
		}
		return false;
	}

	/**
	 * set imported in bulk query. if modified true then set modified to null in bulk query.
	 *
	 * @param $ids
	 * @param $modified
	 */
	private function _setImported($ids, $modified = false)
	{
		try {
			$coreResource = $this->_resource;
			$write        = $coreResource->getConnection( 'core_write' );
			$tableName    = $coreResource->getTableName( 'email_catalog' );
			$ids          = implode( ', ', $ids );

			if ( $modified ) {
				$write->update( $tableName, array( 'modified'   => new \Zend_Db_Expr( 'null' ),
				                                   'updated_at' => gmdate('Y-m-d H:i:s')
				), "product_id IN ($ids)" );
			} else {
				$write->update( $tableName, array( 'imported' => 1,
				                                   'updated_at' => gmdate('Y-m-d H:i:s'), "product_id IN ($ids)" ));
			}
		} catch ( \Exception $e ) {

		}
	}
}