<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dotdigitalgroup\Email\Observer\Customer;


class RegisterWishlist implements \Magento\Framework\Event\ObserverInterface
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
		if($observer->getEvent()->getObject() instanceof \Magento\Wishlist\Model\Wishlist) {
			//wishlist
			$wishlist = $observer->getEvent()->getObject()->getData();
			//required data for checking the new instance of wishlist with items in it.
			if (is_array($wishlist) && isset($wishlist['customer_id']) && isset($wishlist['wishlist_id'])) {

				$wishlistModel      = $this->_wishlist->create()->load( $wishlist['wishlist_id'] );
				$itemsCount = $wishlistModel->getItemsCount();
				//wishlist items found
				if ($itemsCount) {
					//save wishlist info in the table
					$this->_registerWishlist( $wishlist );
				}
			}
		}
	}

	/**
	 * Automation new wishlist program.
	 * @param $wishlist
	 *
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	private function _registerWishlist($wishlist)
	{
		try{
			$emailWishlist = $this->_wishlistFactory->create();
			$customer      = $this->_customerFactory->create();

			//if wishlist exist not to save again
			if(!$emailWishlist->getWishlist($wishlist['wishlist_id'])){
				$customer->load($wishlist['customer_id']);
				$email      = $customer->getEmail();
				$wishlistId = $wishlist['wishlist_id'];
				$websiteId  = $customer->getWebsiteId();
				$emailWishlist->setWishlistId($wishlistId)
	              ->setCustomerId($wishlist['customer_id'])
	              ->setStoreId($customer->getStoreId())
	              ->save();

				$store = $this->_storeManager->getStore($customer->getStoreId());
				$storeName = $store->getName();

				//if api is not enabled
				if (! $this->_helper->isEnabled($websiteId))
					return $this;
				$programId     = $this->_helper->getWebsiteConfig('connector_automation/visitor_automation/wishlist_automation', $websiteId);
				//wishlist program mapped
				if ($programId) {
					$automation = $this->_automationFactory->create();
					//save automation type
					$automation->setEmail( $email )
			           ->setAutomationType( \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_WISHLIST )
			           ->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
			           ->setTypeId( $wishlistId )
			           ->setWebsiteId( $websiteId )
			           ->setStoreName( $storeName )
			           ->setProgramId( $programId );
					$automation->save();
				}
			}
		}catch(\Exception $e){
			$this->_helper->debug((string)$e, array());
		}
	}
}
