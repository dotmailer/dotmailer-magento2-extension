<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
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
		try{
			$object = $observer->getEvent()->getDataObject();
			$productId = $object->getId();

			if ($item = $this->_loadProduct($productId)){
				if ($item->getImported())
					$item->setModified(1)
					     ->save();
			}
		}catch (\Exception $e){
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
