<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dotdigitalgroup\Email\Observer\Customer;


class RemoveWishlist implements \Magento\Framework\Event\ObserverInterface
{
	protected $_helper;
	protected $_registry;
	protected $_logger;
	protected $_storeManager;
	protected $_objectManager;
	protected $_wishlistFactory;
	protected $_customerFactory;
	protected $_contactFactory;
	protected $_automationFactory;
	protected $_proccessorFactory;
	protected $_reviewFactory;
	protected $_wishlist;


	public function __construct(
		\Dotdigitalgroup\Email\Model\ReviewFactory $reviewFactory,
		\Magento\Wishlist\Model\WishlistFactory $wishlist,
		\Dotdigitalgroup\Email\Model\ProccessorFactory $proccessorFactory,
		\Dotdigitalgroup\Email\Model\AutomationFactory $automationFactory,
		\Dotdigitalgroup\Email\Model\ContactFactory $contactFactory,
		\Magento\Customer\Model\CustomerFactory $customerFactory,
		\Dotdigitalgroup\Email\Model\WishlistFactory $wishlistFactory,
		\Magento\Framework\Registry $registry,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Psr\Log\LoggerInterface $loggerInterface,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	) {
		$this->_reviewFactory = $reviewFactory;
		$this->_wishlist = $wishlist;
		$this->_contactFactory = $contactFactory;
		$this->_proccessorFactory  = $proccessorFactory;
		$this->_automationFactory = $automationFactory;
		$this->_customerFactory = $customerFactory;
		$this->_wishlistFactory = $wishlistFactory;
		$this->_helper = $data;
		$this->_logger = $loggerInterface;
		$this->_storeManager = $storeManagerInterface;
		$this->_registry = $registry;
		$this->_objectManager = $objectManagerInterface;
	}

	/**
	 * If it's configured to capture on shipment - do this
	 *
	 * @param \Magento\Framework\Event\Observer $observer
	 * @return $this
	 */
	public function execute(\Magento\Framework\Event\Observer $observer)
	{
		$object = $observer->getEvent()->getDataObject();
		$customer = $this->_customerFactory->create()
		                                   ->load($object->getCustomerId());
		$website = $this->_storeManager->getStore($customer->getStoreId())->getWebsite();

		//sync enabled
		$syncEnabled = $this->_helper->getWebsiteConfig(
			\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
			$website->getId()
		);
		if ($this->_helper->isEnabled($website->getId()) && $syncEnabled) {
			//Remove wishlist
			try {
				$item = $this->_wishlistFactory->create()
				                               ->getWishlist($object->getWishlistId());
				if ($item->getId()) {
					//register in queue with importer
					$this->_proccessorFactory->create()->registerQueue(
						\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_WISHLIST,
						array($item->getId()),
						\Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE_DELETE,
						$website->getId()
					);
					$item->delete();
				}
			} catch (\Exception $e) {
				$this->_helper->debug((string)$e, array());
			}
		}
	}

}
