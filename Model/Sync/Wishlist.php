<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Wishlist
{
	protected $_helper;
	protected $_resource;
	protected $_objectManager;

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\App\Resource $resource,
		\Magento\Framework\StdLib\Datetime $datetime,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
		$this->_helper = $helper;
		$this->_resource = $resource;
		$this->_datetime = $datetime;
		$this->_objectManager = $objectManagerInterface;
	}
	public function sync()
	{
		$response = array('success' => true, 'message' => '');
		//resource allocation
		$this->_helper->allowResourceFullExecution();
		$websites = $this->_helper->getWebsites(true);
		foreach ($websites as $website) {
			$wishlistEnabled = $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED, $website);
			$apiEnabled = $this->_helper->isEnabled($website);
			$storeIds = $website->getStoreIds();
			if ($wishlistEnabled && $apiEnabled && !empty($storeIds)) {
				//using bulk api
				$this->_helper->log('---------- Start wishlist bulk sync ----------');
				$this->_start = microtime(true);
				$this->_exportWishlistForWebsite($website);
				//send wishlist as transactional data
				if (isset($this->_wishlists[$website->getId()])) {
					$websiteWishlists = $this->_wishlists[$website->getId()];

					//register in queue with importer
					$check = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')->registerQueue(
						\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_WISHLIST,
						$websiteWishlists,
						\Dotdigitalgroup\Email\Model\Proccessor::MODE_BULK,
						$website->getId()
					);

					//set imported
					if ($check) {
						$this->_setImported($this->_wishlistIds);
					}
				}
				$message = 'Total time for wishlist bulk sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);
				$this->_helper->log($message);

				//using single api
				$this->_exportWishlistForWebsiteInSingle($website);
			}
		}
		$response['message'] = "wishlist updated: ". $this->_count;
		return $response;
	}

	private function _exportWishlistForWebsite( $website)
	{
		//reset wishlists
		$this->_wishlists = array();
		$this->_wishlistIds = array();
		$limit = $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);

		$collection = $this->_getWishlistToImport($website, $limit);
		foreach($collection as $emailWishlist){
			$customer = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($emailWishlist->getCustomerId());
			$wishlist = $this->_objectManager->create('Magento\Wishlist\Model\Wishlist')->load($emailWishlist->getWishlistId());
			//set customer for wishlist
			$connectorWishlist = $this->_objectManager->create('Dotdigitalgroup\Emal\Model\Customer\Wishlist')
				->setCutomer($customer);
			$connectorWishlist->setId($wishlist->getId())
			                  ->setUpdatedAt($wishlist->getUpdatedAt());
			$wishListItemCollection = $wishlist->getItemCollection();
			if ($wishListItemCollection->getSize()) {
				foreach ($wishListItemCollection as $item) {
					$product = $item->getProduct();
					$wishlistItem = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Customer\Wishlist\Item')
						->setProduct($product)
		                    ->setQty($item->getQty())
		                    ->setPrice($product);
					//store for wishlists
					$connectorWishlist->setItem($wishlistItem);
					$this->_count++;
				}
				//set wishlists for later use
				$this->_wishlists[$website->getId()][] = $connectorWishlist;
				$this->_wishlistIds[] = $emailWishlist->getWishlistId();
			}
		}
	}

	private function _getWishlistToImport($website, $limit = 100)
	{
		$collection = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Wishlist')->getCollection()
               ->addFieldToFilter('wishlist_imported', array('null' => true))
               ->addFieldToFilter('store_id', array('in' => $website->getStoreIds()))
               ->addFieldToFilter('item_count', array('gt' => 0));

		$collection->getSelect()->limit($limit);

		return $collection;
	}

	private function _exportWishlistForWebsiteInSingle( $website)
	{
		$limit = $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_TRANSACTIONAL_DATA_SYNC_LIMIT, $website);
		$collection = $this->_getModifiedWishlistToImport($website, $limit);
		$this->_wishlistIds = array();
		foreach($collection as $emailWishlist){
			$customer = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($emailWishlist->getCustomerId());
			$wishlist = $this->_objectManager->create('Magento\Wishlist\Model\Wishlist')->load($emailWishlist->getWishlistId());
			$connectorWishlist = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Customer\Wishlist')
				->setCustomer($customer);
			$connectorWishlist->setId($wishlist->getId());
			$wishListItemCollection = $wishlist->getItemCollection();
			if ($wishListItemCollection->getSize()) {
				foreach ($wishListItemCollection as $item) {
					$product = $item->getProduct();
					$wishlistItem = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Customer\Wishlist\Item')
						->setProduct($product)
	                    ->setQty($item->getQty())
	                    ->setPrice($product);
					//store for wishlists
					$connectorWishlist->setItem($wishlistItem);
					$this->_count++;
				}
				//send wishlist as transactional data
				$this->_helper->log('---------- Start wishlist single sync ----------');
				$this->_start = microtime(true);
				//register in queue with importer
				$check = $this->_objectManager->create('Dotdigitalgroup\Email\Mode\Proccessor')->registerQueue(
					\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_WISHLIST,
					$connectorWishlist,
					\Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE,
					$website->getId()
				);
				if ($check) {
					$this->_wishlistIds[] = $emailWishlist->getWishlistId();
				}
				$message = 'Total time for wishlist single sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);
				$this->_helper->log($message);
			}else{
				//register in queue with importer
				$check = $this->_objectManager->create('Dotdigitalgroup\Email\Mode\Proccessor')->registerQueue(
					\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_WISHLIST,
					$connectorWishlist,
					\Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE_DELETE,
					$website->getId()
				);

				if ($check) {
					$this->_wishlistIds[] = $emailWishlist->getWishlistId();
				}
				$message = 'Total time for wishlist single sync : ' . gmdate("H:i:s", microtime(true) - $this->_start);
				$this->_helper->log($message);
			}
		}
		if(!empty($this->_wishlistIds))
			$this->_setImported($this->_wishlistIds, true);
	}

	private function _getModifiedWishlistToImport($website, $limit = 100)
	{
		$collection = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Wishlist')->getCollection()
               ->addFieldToFilter('wishlist_modified', 1)
               ->addFieldToFilter('store_id', array('in' => $website->getStoreIds()));

		$collection->getSelect()->limit($limit);
		return $collection;
	}

	/**
	 * Reset the email reviews for reimport.
	 *
	 * @return int
	 */
	public function reset()
	{
		$coreResource = $this->_resource;

		$conn = $coreResource->getConnection('core_write');
		try{
			$num = $conn->update($coreResource->getTableName('email_wishlist'),
				array('wishlist_imported' => new \Zend_Db_Expr('null'), 'wishlist_modified' => new \Zend_Db_Expr('null'))
			);
		}catch (\Exception $e){
		}

		return $num;
	}

	/**
	 * set imported in bulk query
	 *
	 * @param $ids
	 * @param $modified
	 */
	private function _setImported($ids, $modified = false)
	{
		try{
			$coreResource = $this->_resource;
			$write = $coreResource->getConnection('core_write');
			$tableName = $coreResource->getTableName('email_wishlist');
			$ids = implode(', ', $ids);
			$now = new \Datetime();
			$nowDate = $this->_datetime->formatDate($now->getTimestamp());

			if($modified)
				$write->update($tableName, array('wishlist_modified' => new \Zend_Db_Expr('null'), 'updated_at' => $nowDate), "wishlist_id IN ($ids)");
			else
				$write->update($tableName, array('wishlist_imported' => 1, 'updated_at' => $nowDate), "wishlist_id IN ($ids)");
		}catch (\Exception $e){

		}
	}
}