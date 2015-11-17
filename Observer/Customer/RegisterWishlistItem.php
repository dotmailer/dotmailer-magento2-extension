<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dotdigitalgroup\Email\Observer\Customer;


class RegisterWishlistItem implements \Magento\Framework\Event\ObserverInterface
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
		$object        = $observer->getEvent()->getDataObject();
		$wishlist      = $this->_wishlist->create()->load( $object->getWishlistId() );
		$emailWishlist = $this->_wishlistFactory->create();
		try {
			if ( $object->getWishlistId() ) {

				$itemCount = count( $wishlist->getItemCollection() );
				$item      = $emailWishlist->getWishlist( $object->getWishlistId() );

				if ( $item && $item->getId() ) {
					$preSaveItemCount = $item->getItemCount();

					if ( $itemCount != $item->getItemCount() ) {
						$item->setItemCount( $itemCount );
					}

					if ( $itemCount == 1 && $preSaveItemCount == 0 ) {
						$item->setWishlistImported( null );
					} elseif ( $item->getWishlistImported() ) {
						$item->setWishlistModified( 1 );
					}

					$item->save();
				}
			}
		} catch ( \Exception $e ) {
			$this->_helper->debug((string)$e, array());
		}

		return $this;
	}

}
