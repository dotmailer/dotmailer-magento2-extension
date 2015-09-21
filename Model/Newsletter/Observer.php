<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

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
	 * Change the subscribsion for an contact.
	 * Add new subscribers to an automation.
	 *
	 *
	 * @return $this
	 */
	public function handleNewsletterSubscriberSave($observer)
	{
		$subscriber = $observer->getEvent()->getSubscriber();
		$email              = $subscriber->getEmail();
		$storeId            = $subscriber->getStoreId();
		$subscriberStatus   = $subscriber->getSubscriberStatus();
		$websiteId = $this->_storeManager->getStore($subscriber->getStoreId())->getWebsiteId();

		//check if enabled
		$apiEnabled = $this->_helper->isEnabled($websiteId);
		if ( ! $apiEnabled)
			return $this;

		try{
			// fix for a multiple hit of the observer
			$emailReg =  $this->_registry->registry($email . '_subscriber_save');
			if ($emailReg){
				return $this;
			}
			$this->_registry->register($email . '_subscriber_save', $email);

			$contactEmail = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Contact')->loadByCustomerEmail($email, $websiteId);

			// only for subsribers
			if ($subscriberStatus == \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED) {
				$client = $this->_helper->getWebsiteApiClient($websiteId);
				//check for website client
				if ($client) {
					//set contact as subscribed
					$contactEmail->setSubscriberStatus( $subscriberStatus )
						->setIsSubscriber('1');
					$apiContact = $client->postContacts( $email );

					//resubscribe suppressed contacts
					if (isset($apiContact->message) && $apiContact->message == \Dotdigitalgroup\Email\Model\Apiconnector\Client::API_ERROR_CONTACT_SUPPRESSED) {
						$client->postContactsResubscribe( $apiContact );
					}
				}
				// reset the subscriber as suppressed
				$contactEmail->setSuppressed(null);

				//not subscribed
			} else {
				//skip if contact is suppressed
				if ($contactEmail->getSuppressed())
					return $this;
				//update contact id for the subscriber
				$client = $this->_helper->getWebsiteApiClient($websiteId);
				//check for website client
				if ($client) {
					$contactId = $contactEmail->getContactId();
					//get the contact id
					if ( !$contactId ) {
						//if contact id is not set get the contact_id
						$result = $client->postContacts( $email );
						if ( isset( $result->id ) ) {
							$contactId = $result->id;
						} else {
							//no contact id skip
							$contactEmail->setSuppressed( '1' )
								->save();
							return $this;
						}
					}
					//remove contact from address book
					$client->deleteAddressBookContact( $this->_helper->getSubscriberAddressBook( $websiteId ), $contactId );
				}
				$contactEmail->setIsSubscriber(null)
					->setSubscriberStatus(\Magento\Newsletter\Model\Subscriber::STATUS_UNSUBSCRIBED);
			}

			//add subscriber to automation
			$this->_addSubscriberToAutomation($email, $subscriber, $websiteId);

			//update the contact
			$contactEmail->setStoreId($storeId);
			if (isset($contactId))
				$contactEmail->setContactId($contactId);
			//update contact
			$contactEmail->save();

		}catch(\Exception $e){
		}
		return $this;
	}

	private function _addSubscriberToAutomation($email, $subscriber, $websiteId){

		$storeId            = $subscriber->getStoreId();
		$store = $this->_storeManager->getStore($storeId);
		$programId = $this->_helper->getWebsiteConfig('connector_automation/visitor_automation/subscriber_automation', $websiteId);
		//not mapped ignore
		if (! $programId)
			return;
		try {
			//check the subscriber alredy exists
			$enrolment = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Automation')->getCollection()
				->addFieldToFilter('email', $email)
				->addFieldToFilter('automation_type', \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_SUBSCRIBER)
				->addFieldToFilter('website_id', $websiteId)
				->getFirstItem();

			//add new subscriber to automation
			if (! $enrolment->getId()) {
				//save subscriber to the queue
				$automation = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Automation');
				$automation->setEmail( $email )
					->setAutomationType( \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_TYPE_NEW_SUBSCRIBER )
					->setEnrolmentStatus( \Dotdigitalgroup\Email\Model\Sync\Automation::AUTOMATION_STATUS_PENDING )
					->setTypeId( $subscriber->getId() )
					->setWebsiteId( $websiteId )
					->setStoreName( $store->getName() )
					->setProgramId( $programId );
				$automation->save();
			}
		}catch(\Exception $e){
		}
	}

}