<?php

namespace Dotdigitalgroup\Email\Model;

class Campaign extends \Magento\Framework\Model\AbstractModel
{
	//xml path configuration
	const XML_PATH_LOSTBASKET_1_ENABLED      = 'abandoned_carts/customers/enabled_1';
	const XML_PATH_LOSTBASKET_2_ENABLED      = 'abandoned_carts/customers/enabled_2';
	const XML_PATH_LOSTBASKET_3_ENABLED      = 'abandoned_carts/customers/enabled_3';

	const XML_PATH_LOSTBASKET_1_INTERVAL     = 'abandoned_carts/customers/send_after_1';
	const XML_PATH_LOSTBASKET_2_INTERVAL     = 'abandoned_carts/customers/send_after_2';
	const XML_PATH_LOSTBASKET_3_INTERVAL     = 'abandoned_carts/customers/send_after_3';

	const XML_PATH_TRIGGER_1_CAMPAIGN        = 'abandoned_carts/customers/campaign_1';
	const XML_PATH_TRIGGER_2_CAMPAIGN        = 'abandoned_carts/customers/campaign_2';
	const XML_PATH_TRIGGER_3_CAMPAIGN        = 'abandoned_carts/customers/campaign_3';

	const XML_PATH_GUEST_LOSTBASKET_1_ENABLED  = 'abandoned_carts/guests/enabled_1';
	const XML_PATH_GUEST_LOSTBASKET_2_ENABLED  = 'abandoned_carts/guests/enabled_2';
	const XML_PATH_GUEST_LOSTBASKET_3_ENABLED  = 'abandoned_carts/guests/enabled_3';

	const XML_PATH_GUEST_LOSTBASKET_1_INTERVAL = 'abandoned_carts/guests/send_after_1';
	const XML_PATH_GUEST_LOSTBASKET_2_INTERVAL = 'abandoned_carts/guests/send_after_2';
	const XML_PATH_GUEST_LOSTBASKET_3_INTERVAL = 'abandoned_carts/guests/send_after_3';

	const XML_PATH_GUEST_LOSTBASKET_1_CAMPAIGN = 'abandoned_carts/guests/campaign_1';
	const XML_PATH_GUEST_LOSTBASKET_2_CAMPAIGN = 'abandoned_carts/guests/campaign_2';
	const XML_PATH_GUEST_LOSTBASKET_3_CAMPAIGN = 'abandoned_carts/guests/campaign_3';


	//error messages
	const SEND_EMAIL_CONTACT_ID_MISSING = 'Error : missing contact id - will try later to send ';

	/**
	 * constructor
	 */
	public function _construct()
	{
		parent::_construct();
		$this->_init('Dotdigitalgroup\Email\Model\Resource\Campaign');
	}

	/**
	 * @param $quoteId
	 * @param $storeId
	 * @return mixed
	 */
	public function loadByQuoteId($quoteId, $storeId)
	{
		$collection = $this->getCollection()
           ->addFieldToFilter('quote_id', $quoteId)
           ->addFieldToFilter('store_id', $storeId);

		if ($collection->getSize()) {
			return $collection->getFirstItem();
		} else {
			$this->setQuoteId($quoteId)
			     ->setStoreId($storeId);
		}

		return $this;
	}


	/**
	 * Sending the campaigns.
	 */
	public function sendCampaigns()
	{
		//grab the emails not send
		$emailsToSend = $this->_getEmailCampaigns();

		foreach ($emailsToSend as $campaign) {

			$email      = $campaign->getEmail();
			$storeId    = $campaign->getStoreId();
			$campaignId = $campaign->getCampaignId();
			$store = Mage::app()->getStore($storeId);
			$websiteId      = $store->getWebsiteId();


			if (!$campaignId) {
				$campaign->setMessage('Missing campaign id: ' . $campaignId)
				         ->setIsSent(1)
				         ->save();
				continue;
			} elseif (!$email) {
				$campaign->setMessage('Missing email : ' . $email)
				         ->setIsSent(1)
				         ->save();
				continue;
			}
			try{
				$client = Mage::helper('ddg')->getWebsiteApiClient($websiteId);
				$contactId = Mage::helper('ddg')->getContactId($campaign->getEmail(), $websiteId);
				if(is_numeric($contactId)) {
					$response = $client->postCampaignsSend($campaignId, array($contactId));
					if (isset($response->message)) {
						//update  the failed to send email message
						$campaign->setMessage($response->message)->setIsSent(1)->save();
					}
					$now = Mage::getSingleton('core/date')->gmtDate();
					//record suscces
					$campaign->setIsSent(1)
							 ->setMessage(NULL)
							 ->setSentAt($now)
							 ->save();
				}else{
					//update  the failed to send email message- error message from post contact
					$campaign->setContactMessage($contactId)->setIsSent(1)->save();
				}
			}catch(Exception $e){
				Mage::logException($e);
			}
		}
		return;
	}

	/**
	 * @return mixed
	 */
	private function _getEmailCampaigns()
	{
		$emailCollection = $this->getCollection();
		$emailCollection->addFieldToFilter('is_sent', array('null' => true))
		                ->addFieldToFilter('campaign_id', array('notnull' => true));
		$emailCollection->getSelect()->order('campaign_id');
		return $emailCollection;
	}
}