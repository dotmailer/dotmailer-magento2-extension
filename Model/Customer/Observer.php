<?php

namespace Dotdigitalgroup\Email\Model\Customer;

class Observer
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
		$this->_reviewFactory     = $reviewFactory;
		$this->_wishlist          = $wishlist;
		$this->_contactFactory    = $contactFactory;
		$this->_proccessorFactory = $proccessorFactory;
		$this->_automationFactory = $automationFactory;
		$this->_customerFactory   = $customerFactory;
		$this->_wishlistFactory   = $wishlistFactory;
		$this->_helper            = $data;
		$this->_logger            = $loggerInterface;
		$this->_storeManager      = $storeManagerInterface;
		$this->_registry          = $registry;
		$this->_objectManager     = $objectManagerInterface;

	}

	/**
	 * Create new contact or update info, also check for email change.
	 *
	 * @param $observer
	 *
	 * @return $this
	 */
	public function handleCustomerSaveAfter($observer)
	{
		$customer = $observer->getEvent()->getCustomer();

		$email        = $customer->getEmail();
		$websiteId    = $customer->getWebsiteId();
		$customerId   = $customer->getEntityId();
		$isSubscribed = $customer->getIsSubscribed();

		try {
			// fix for a multiple hit of the observer
			$emailReg = $this->_registry->registry($email . '_customer_save');
			if ($emailReg) {
				return $this;
			}
			$this->_registry->register($email . '_customer_save', $email);
			$emailBefore  = $this->_customerFactory->create()->load(
				$customer->getId()
			)->getEmail();
			$contactModel = $this->_contactFactory->create()
				->loadByCustomerEmail($emailBefore, $websiteId);
			//email change detection
			if ($email != $emailBefore) {
				$this->_helper->log(
					'email change detected : ' . $email . ', after : '
					. $emailBefore . ', website id : ' . $websiteId
				);
				if ($this->_helper->isEnabled($websiteId)) {
					$client                 = $this->_helper->getWebsiteApiClient(
						$websiteId
					);
					$subscribersAddressBook = $this->_helper->getWebsiteConfig(
						\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SUBSCRIBERS_ADDRESS_BOOK_ID,
						$websiteId
					);
					$response               = $client->postContacts(
						$emailBefore
					);
					//check for matching email
					if (isset($response->id)) {
						if ($email != $response->email) {
							$data = array(
								'Email'     => $email,
								'EmailType' => 'Html'
							);
							//update the contact with same id - different email
							$client->updateContact($response->id, $data);

						}
						if ( ! $isSubscribed
							&& $response->status == 'Subscribed'
						) {
							$client->deleteAddressBookContact(
								$subscribersAddressBook, $response->id
							);
						}
					} elseif (isset($response->message)) {
						$this->_helper->log(
							'Email change error : ' . $response->message
						);
					}
				}
				$contactModel->setEmail($email);
			}
			$contactModel->setEmailImported(
				\Dotdigitalgroup\Email\Model\Contact::EMAIL_CONTACT_NOT_IMPORTED
			)
				->setCustomerId($customerId)
				->save();
		} catch (\Exception $e) {
			$this->_helper->debug((string)$e, array());
		}

		return $this;
	}

	/**
	 * Add new customers to the automation.
	 *
	 * @param $observer
	 *
	 * @return $this
	 */
	public function handleCustomerRegiterSuccess($observer)
	{
		$customer = $observer->getEvent()->getCustomer();
		$email    = $customer->getEmail();
		// fix for a multiple hit of the observer
		$emailReg = $this->_registry->registry($email . '_customer_register');
		if ($emailReg) {
			return $this;
		}
		$this->_registry->register($email . '_customer_register', $email);
		$websiteId = $customer->getWebsiteId();

		$website   = $this->_storeManager->getWebsite($websiteId);
		$store     = $this->_storeManager->getStore($customer->getStoreId());
		$storeName = $store->getName();

		//if api is not enabled
		if ( ! $website->getConfig(
			\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_API_ENABLED
		)
		) {
			return $this;
		}

		try {
			//program id must be map
			$programId = $this->_helper->getWebsiteConfig(
				'connector_automation/visitor_automation/customer_automation',
				$websiteId
			);

			if ( ! $programId) {
				return $this;
			}
			$email      = $customer->getEmail();
			$automation = $this->_automationFactory->create();
			//save automation for new customer
			$automation->setEmail($email)
				->setAutomationType(
					\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_CUSTOMER
				)
				->setEnrolmentStatus(
					\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING
				)
				->setTypeId($customer->getId())
				->setWebsiteId($websiteId)
				->setStoreName($storeName)
				->setProgramId($programId);

			$automation->save();
		} catch (\Exception $e) {
			$this->_helper->debug((string)$e, array());
		}

		return $this;
	}

	/**
	 * Remove the contact on customer delete.
	 *
	 * @return $this
	 */
	public function handleCustomerDeleteAfter($observer)
	{
		$customer     = $observer->getEvent()->getCustomer();
		$email        = $customer->getEmail();
		$websiteId    = $customer->getWebsiteId();
		$apiEnabled   = $this->_helper->isEnabled($websiteId);
		$customerSync = $this->_helper->getCustomerSyncEnabled($websiteId);

		/**
		 * Remove contact.
		 */
		if ($apiEnabled && $customerSync) {
			try {
				//register in queue with importer
				$this->_proccessorFactory->create()->registerQueue(
					\Dotdigitalgroup\Email\Model\Proccessor::IMPORT_TYPE_CONTACT,
					$email,
					\Dotdigitalgroup\Email\Model\Proccessor::MODE_CONTACT_DELETE,
					$websiteId
				);
				$contactModel = $this->_contactFactory->create()
					->loadByCustomerEmail($email, $websiteId);
				if ($contactModel->getId()) {
					//remove contact
					$contactModel->delete();
				}
			} catch (\Exception $e) {
				$this->_helper->debug((string)$e, array());
			}
		}

		return $this;
	}

	/**
	 * Set contact to re-import if registered customer submitted a review. Save review in email_review table.
	 *
	 * @return $this
	 */
	public function reviewSaveAfter($observer)
	{
		$dataObject = $observer->getEvent()->getDataObject();

		if ($dataObject->getCustomerId()
			&& $dataObject->getStatusId()
			== \Magento\Review\Model\Review::STATUS_APPROVED
		) {
			$customerId = $dataObject->getCustomerId();
			$this->_helper->setConnectorContactToReImport($customerId);
			//save review info in the table
			$this->_registerReview($dataObject);
			$store     = $this->_storeManager->getStore(
				$dataObject->getStoreId()
			);
			$storeName = $store->getName();
			$website   = $this->_storeManager->getStore($store)->getWebsite();
			$customer  = $this->_customerFactory->create()
				->load($customerId);
			//if api is not enabled
			if ( ! $this->_helper->isEnabled($website)) {
				return $this;
			}

			$programId = $this->_helper->getWebsiteConfig(
				'connector_automation/visitor_automation/review_automation'
			);
			if ($programId) {
				$automation = $this->_automationFactory->create();
				$automation->setEmail($customer->getEmail())
					->setAutomationType(
						\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_REVIEW
					)
					->setEnrolmentStatus(
						\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING
					)
					->setTypeId($dataObject->getReviewId())
					->setWebsiteId($website->getId())
					->setStoreName($storeName)
					->setProgramId($programId);
				$automation->save();
			}
		}

		return $this;
	}

	/**
	 * register review.
	 *
	 * @param $review
	 */
	protected function _registerReview($review)
	{
		try {
			$this->_reviewFactory->create()
				->setReviewId($review->getReviewId())
				->setCustomerId($review->getCustomerId())
				->setStoreId($review->getStoreId())
				->save();
		} catch (\Exception $e) {
			$this->_helper->debug((string)$e, array());
		}
	}

	/**
	 * wishlist save after observer. save new wishlist in the email_wishlist table.
	 *
	 * @return $this
	 */
	public function wishlistSaveAfter($observer)
	{
		if ($observer->getEvent()->getObject() instanceof
			\Magento\Wishlist\Model\Wishlist
		) {
			//wishlist
			$wishlist = $observer->getEvent()->getObject()->getData();
			//required data for checking the new instance of wishlist with items in it.
			if (is_array($wishlist) && isset($wishlist['customer_id'])
				&& isset($wishlist['wishlist_id'])
			) {

				$wishlistModel = $this->_wishlist->create()->load(
					$wishlist['wishlist_id']
				);
				$itemsCount    = $wishlistModel->getItemsCount();
				//wishlist items found
				if ($itemsCount) {
					//save wishlist info in the table
					$this->_registerWishlist($wishlist);
				}
			}
		}
	}

	/**
	 * Automation new wishlist program.
	 *
	 * @param $wishlist
	 *
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function _registerWishlist($wishlist)
	{
		try {
			$emailWishlist = $this->_wishlistFactory->create();
			$customer      = $this->_customerFactory->create();

			//if wishlist exist not to save again
			if ( ! $emailWishlist->getWishlist($wishlist['wishlist_id'])) {
				$customer->load($wishlist['customer_id']);
				$email      = $customer->getEmail();
				$wishlistId = $wishlist['wishlist_id'];
				$websiteId  = $customer->getWebsiteId();
				$emailWishlist->setWishlistId($wishlistId)
					->setCustomerId($wishlist['customer_id'])
					->setStoreId($customer->getStoreId())
					->save();

				$store     = $this->_storeManager->getStore(
					$customer->getStoreId()
				);
				$storeName = $store->getName();

				//if api is not enabled
				if ( ! $this->_helper->isEnabled($websiteId)) {
					return $this;
				}
				$programId = $this->_helper->getWebsiteConfig(
					'connector_automation/visitor_automation/wishlist_automation',
					$websiteId
				);
				//wishlist program mapped
				if ($programId) {
					$automation = $this->_automationFactory->create();
					//save automation type
					$automation->setEmail($email)
						->setAutomationType(
							\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_WISHLIST
						)
						->setEnrolmentStatus(
							\Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING
						)
						->setTypeId($wishlistId)
						->setWebsiteId($websiteId)
						->setStoreName($storeName)
						->setProgramId($programId);
					$automation->save();
				}
			}
		} catch (\Exception $e) {
			$this->_helper->debug((string)$e, array());
		}
	}

	/**
	 * wishlist item save after/ item delete after
	 *
	 */
	public function wishlistItemSaveAfter($observer)
	{
		$object        = $observer->getEvent()->getDataObject();
		$wishlist      = $this->_wishlist->create()->load(
			$object->getWishlistId()
		);
		$emailWishlist = $this->_wishlistFactory->create();
		try {
			if ($object->getWishlistId()) {

				$itemCount = count($wishlist->getItemCollection());
				$item      = $emailWishlist->getWishlist(
					$object->getWishlistId()
				);

				if ($item && $item->getId()) {
					$preSaveItemCount = $item->getItemCount();

					if ($itemCount != $item->getItemCount()) {
						$item->setItemCount($itemCount);
					}

					if ($itemCount == 1 && $preSaveItemCount == 0) {
						$item->setWishlistImported(null);
					} elseif ($item->getWishlistImported()) {
						$item->setWishlistModified(1);
					}

					$item->save();
				}
			}
		} catch (\Exception $e) {
			$this->_helper->debug((string)$e, array());
		}
	}

	/**
	 * wishlist item delete observer
	 *
	 */
	public function wishlistDeleteAfter($observer)
	{
		$object   = $observer->getEvent()->getDataObject();
		$customer = $this->_customerFactory->create()
			->load($object->getCustomerId());
		$website  = $this->_storeManager->getStore($customer->getStoreId())
			->getWebsite();

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