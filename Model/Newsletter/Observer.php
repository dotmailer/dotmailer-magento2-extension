<?php

class Dotdigitalgroup_Email_Model_Newsletter_Observer
{

	/**
	 * Change the subscribsion for an contact.
	 * Add new subscribers to an automation.
	 *
	 * @param Varien_Event_Observer $observer
	 *
	 * @return $this
	 */
	public function handleNewsletterSubscriberSave(Varien_Event_Observer $observer)
	{
		$helper = Mage::helper('ddg');
		$subscriber = $observer->getEvent()->getSubscriber();
		$email              = $subscriber->getEmail();
		$storeId            = $subscriber->getStoreId();
		$subscriberStatus   = $subscriber->getSubscriberStatus();
		$websiteId = Mage::app()->getStore($subscriber->getStoreId())->getWebsiteId();

		//check if enabled
		if ( ! Mage::helper('ddg')->isEnabled($websiteId))
			return $this;
		try{

			// fix for a multiple hit of the observer
			$emailReg =  Mage::registry($email . '_subscriber_save');
			if ($emailReg){
				return $this;
			}
			Mage::register($email . '_subscriber_save', $email);

			$contactEmail = Mage::getModel('ddg_automation/contact')->loadByCustomerEmail($email, $websiteId);

			// only for subsribers
			if ($subscriberStatus == Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED) {
				$client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);
				//check for website client
				if ($client) {
					//set contact as subscribed
					$contactEmail->setSubscriberStatus( $subscriberStatus )
						->setIsSubscriber('1');
					$apiContact = $client->postContacts( $email );

					//resubscribe suppressed contacts
					if (isset($apiContact->message) && $apiContact->message == Dotdigitalgroup_Email_Model_Apiconnector_Client::API_ERROR_CONTACT_SUPPRESSED) {
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
				$client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);
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
					$client->deleteAddressBookContact( $helper->getSubscriberAddressBook( $websiteId ), $contactId );
				}
				$contactEmail->setIsSubscriber(null)
					->setSubscriberStatus(Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED);
			}

			//add subscriber to automation
			$this->_addSubscriberToAutomation($email, $subscriber, $websiteId);

			//update the contact
			$contactEmail->setStoreId($storeId);
			if (isset($contactId))
				$contactEmail->setContactId($contactId);
			//update contact
			$contactEmail->save();

		}catch(Exception $e){
			Mage::logException($e);
			Mage::helper('ddg')->getRaygunClient()->SendException($e, array(Mage::getBaseUrl('web')));
		}
		return $this;
	}

	private function _addSubscriberToAutomation($email, $subscriber, $websiteId){

		$storeId            = $subscriber->getStoreId();
		$store = Mage::app()->getStore($storeId);
		$programId     = Mage::helper( 'ddg' )->getAutomationIdByType( 'XML_PATH_CONNECTOR_AUTOMATION_STUDIO_SUBSCRIBER', $websiteId );
		//not mapped ignore
		if (! $programId)
			return;
		try {
			//check the subscriber alredy exists
			$enrolment = Mage::getModel('ddg_automation/automation')->getCollection()
				->addFieldToFilter('email', $email)
				->addFieldToFilter('automation_type', Dotdigitalgroup_Email_Model_Automation::AUTOMATION_TYPE_NEW_SUBSCRIBER)
				->addFieldToFilter('website_id', $websiteId)
				->getFirstItem();

			//add new subscriber to automation
			if (! $enrolment->getId()) {
				//save subscriber to the queue
				$automation = Mage::getModel( 'ddg_automation/automation' );
				$automation->setEmail( $email )
					->setAutomationType( Dotdigitalgroup_Email_Model_Automation::AUTOMATION_TYPE_NEW_SUBSCRIBER )
					->setEnrolmentStatus( Dotdigitalgroup_Email_Model_Automation::AUTOMATION_STATUS_PENDING )
					->setTypeId( $subscriber->getId() )
					->setWebsiteId( $websiteId )
					->setStoreName( $store->getName() )
					->setProgramId( $programId );
				$automation->save();
			}
		}catch(Exception $e){
			Mage::logException($e);
		}
	}

}