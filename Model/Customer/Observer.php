<?php

namespace Dotdigitalgroup\Email\Model\Customer;

class Observer
{
	protected $_helper;
	protected $_registry;
	protected $_logger;
	protected $_storeManager;
	protected $_objectManager;

	public function __construct(
		\Magento\Framework\Registry $registry,
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Psr\Log\LoggerInterface $loggerInterface,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface
	)
	{
		$this->_helper = $data;
		$this->_logger = $loggerInterface;
		$this->_storeManager = $storeManagerInterface;
		$this->_registry = $registry;
		$this->_objectManager = $objectManagerInterface;

	}
	/**
	 * Create new contact or update info, also check for email change
	 * event: customer_save_after
	 * @return $this
	 */
	public function handleCustomerSaveAfter( $observer)
	{
		$customer = $observer->getEvent()->getCustomer();

		$email      = $customer->getEmail();
		$websiteId  = $customer->getWebsiteId();
		$customerId = $customer->getEntityId();
		$isSubscribed = $customer->getIsSubscribed();

		try{
			// fix for a multiple hit of the observer
			$emailReg =  $this->_registry->registry($email . '_customer_save');
			if ($emailReg){
				return $this;
			}
			$this->_registry->register($email . '_customer_save', $email);
			$emailBefore = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($customer->getId())->getEmail();
			$contactModel = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Contact')->loadByCustomerEmail($emailBefore, $websiteId);
			//email change detection
			if ($email != $emailBefore) {
				$this->_helper->log('email change detected : '  . $email . ', after : ' . $emailBefore .  ', website id : ' . $websiteId);
				$enabled = $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED, $websiteId);
				if ($enabled) {
					$client = $this->_helper->getWebsiteApiClient($websiteId);
					$subscribersAddressBook = $this->_helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID, $websiteId);
					$response = $client->postContacts($emailBefore);
					//check for matching email
					if (isset($response->id)) {
						if ($email != $response->email) {
							$data = array(
								'Email' => $email,
								'EmailType' => 'Html'
							);
							//update the contact with same id - different email
							$client->updateContact($response->id, $data);

						}
						if (!$isSubscribed && $response->status == 'Subscribed') {
							$client->deleteAddressBookContact($subscribersAddressBook, $response->id);
						}
					} elseif (isset($response->message)) {
						$this->_helper->log('Email change error : ' . $response->message);
					}
				}
				$contactModel->setEmail($email);
			}
$this->_logger->info('saving contact');
			$contactModel->setEmailImported(\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED)
				->setCustomerId($customerId)
				->save();
		}catch(\Exception $e){
			$this->_logger->critical($e->getMessage());
		}
		return $this;
	}

	/**
	 * Add new customers to the automation.
	 *
	 * @return $this
	 */
	public function handleCustomerRegiterSuccess($observer)
	{
		$customer = $observer->getEvent()->getCustomer();
		$email = $customer->getEmail();
		// fix for a multiple hit of the observer
		$emailReg =  $this->_registry->registry($email . '_customer_register');
		if ($emailReg){
			return $this;
		}
		$this->_registry->register($email . '_customer_register', $email);
		$websiteId  = $customer->getWebsiteId();

		$website = $this->_storeManager->getWebsite($websiteId);
		$store = $this->_storeManager->getStore($customer->getStoreId());
		$storeName = $store->getName();

		//if api is not enabled
		if (!$website->getConfig(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED))
			return $this;

		try {
			//program id must be map
			$programId     = $this->_helper->getWebsiteConfig('connector_automation/visitor_automation/customer_automation', $websiteId);

			if (!$programId)
				return $this;
			$email      = $customer->getEmail();
			$automation = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Automation');
			$automation->setEmail( $email )
				->setAutomationType( \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_CUSTOMER )
				->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
				->setTypeId( $customer->getId() )
				->setWebsiteId($websiteId)
				->setStoreName($storeName)
				->setProgramId($programId)
			;

			$automation->save();
		}catch(\Exception $e) {
			$this->_helper->log($e->getMessage());
		}

		return $this;
	}

	/**
	 * Remove the contact on customer delete.
	 *
	 * @return $this
	 */
	public function handleCustomerDeleteAfter( $observer)
	{
		$customer = $observer->getEvent()->getCustomer();
		$email      = $customer->getEmail();
		$websiteId  = $customer->getWebsiteId();
		$apiEnabled = $this->_helper->isEnabled($websiteId);
		$customerSync = $this->_helper->getCustomerSyncEnabled($websiteId);

		/**
		 * Remove contact.
		 */
		if ($apiEnabled && $customerSync) {
			try {
				//register in queue with importer
				$check = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')->registerQueue(
					\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_CONTACT,
					$email,
					\Dotdigitalgroup\Email\Model\Proccessor::MODE_CONTACT_DELETE,
					$websiteId
				);
				$contactModel = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Contact')->loadByCustomerEmail($email, $websiteId);
				if ($contactModel->getId() && $check) {
					//remove contact
					$contactModel->delete();
				}
			} catch (\Exception $e) {
			}
		}
		return $this;
	}

	/**
	 * Set contact to re-import if registered customer submitted a review. Save review in email_review table.
	 * @return $this
	 */
	public function reviewSaveAfter( $observer)
	{
		$dataObject = $observer->getEvent()->getDataObject();

		if ($dataObject->getCustomerId() && $dataObject->getStatusId() == \Magento\Review\Model\Review::STATUS_PENDING){
			$customerId = $dataObject->getCustomerId();
			$this->_helper->setConnectorContactToReImport($customerId);
			//save review info in the table
			$this->_registerReview($dataObject);
			$store = $this->_storeManager->getStore($dataObject->getStoreId());
			$storeName = $store->getName();
			$website = $this->_storeManager->getStore($store)->getWebsite();
			$customer = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($customerId);
			//if api is not enabled
			if (! $this->_helper->isEnabled($website))
				return $this;

			$programId = $this->_helper->getWebsiteConfig('connector_automation/visitor_automation/review_automation');
			if ($programId) {
				$automation = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Automation');
				$automation->setEmail( $customer->getEmail() )
					->setAutomationType( \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_REVIEW )
					->setEnrolmentStatus(\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING)
					->setTypeId( $dataObject->getReviewId() )
					->setWebsiteId( $website->getId() )
					->setStoreName( $storeName )
					->setProgramId( $programId );
				$automation->save();
			}
		}
		return $this;
	}

	/**
	 * register review
	 *
	 * @param $review
	 */
	private function _registerReview($review)
	{
		try{
			$emailReview = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Review');
			$emailReview->setReviewId($review->getReviewId())
				->setCustomerId($review->getCustomerId())
				->setStoreId($review->getStoreId())
				->save();
		}catch(\Exception $e){
		}
	}

	/**
	 * wishlist save after observer. save new wishlist in the email_wishlist table.
	 *
	 * @return $this
	 */
	public function wishlistSaveAfter($observer)
	{
		if($observer->getEvent()->getObject() instanceof \Magento\Wishlist\Model\Wishlist) {
			$wishlist = $observer->getEvent()->getObject()->getData();
			if (is_array($wishlist) && isset($wishlist['customer_id'])) {
				//save wishlist info in the table
				$this->_registerWishlist( $wishlist );
			}
		}
	}

	/**
	 * register wishlist
	 *
	 * @param $wishlist
	 * @return $this
	 */
	private function _registerWishlist($wishlist)
	{
		try{
			$emailWishlist = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Wishlist');
			$customer = $this->_objectManager->create('Magento\Customer\Model\Customer');

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
				$website = $this->_storeManager->getStore($store)->getWebsite();

				//if api is not enabled
				if (! $this->_helper->isEnabled($website))
					return $this;
				$programId     = $this->_helper->getWebsiteConfig('connector_automation/visitor_automation/wishlist_automation', $websiteId);
				if ($programId) {
					$automation = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Automation');
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
		}
	}

	/**
	 * wishlist item save after
	 *
	 */
	public function wishlistItemSaveAfter($observer)
	{
		$object        = $observer->getEvent()->getDataObject();
		$wishlist      = $this->_objectManager->create('Magento\Wishlist\Model\Wishlist')->load( $object->getWishlistId() );
		$emailWishlist = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Wishlist');
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
		}
	}

	/**
	 * wishlist delete observer
	 *
	 */
	public function wishlistDeleteAfter($observer)
	{
		$object = $observer->getEvent()->getDataObject();
		$customer = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($object->getCustomerId());
		$website = $this->_storeManager->getStore($customer->getStoreId())->getWebsite();

		//sync enabled
		$syncEnabled = $this->_helper->getWebsiteConfig(
			\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_WISHLIST_ENABLED,
			$website->getId()
		);
		if ($this->_helper->isEnabled($website->getId()) && $syncEnabled) {
			//Remove wishlist
			try {
				$item = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Wishlist')->getWishlist($object->getWishlistId());
				if ($item->getId()) {
					//register in queue with importer
					$check = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Proccessor')->registerQueue(
						\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_WISHLIST,
						array($item->getId()),
						\Dotdigitalgroup\Email\Model\Proccessor::MODE_SINGLE_DELETE,
						$website->getId()
					);
					if ($check) {
						$item->delete();
					}
				}
			} catch (\Exception $e) {
			}
		}
	}
}