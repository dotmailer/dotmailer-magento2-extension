<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Camapaign
{
	protected $_helper;
	protected $_objectManager;
	protected $_storeManger;


	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface
	)
	{
		$this->_helper = $data;
		$this->_objectManager = $objectManagerInterface;
		$this->_storeManager = $storeManagerInterface;

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
			$store = $this->_storeManager->getStore($storeId);
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
				$client = $this->_helper->getWebsiteApiClient($websiteId);
				$contactId = $this->_helper->getContactId($campaign->getEmail(), $websiteId);
				if(is_numeric($contactId)) {
					$response = $client->postCampaignsSend($campaignId, array($contactId));
					if (isset($response->message)) {
						//update  the failed to send email message
						$campaign->setMessage($response->message)->setIsSent(1)->save();
					}

					//record suscces
					$campaign->setIsSent(1)
					         ->setMessage(NULL)
					         ->setSentAt(gmdate('Y-m-d H:i:s'))
					         ->save();
				}else{
					//update  the failed to send email message- error message from post contact
					$campaign->setContactMessage($contactId)->setIsSent(1)->save();
				}
			}catch(\Exception $e){
			}
		}
		return;
	}


	/**
	 * @return mixed
	 */
	private function _getEmailCampaigns()
	{
		$emailCollection = $this->_objectManager->create('Dotdigitalgroup\Email\Model\Campaaign')->getCollection();
		$emailCollection->addFieldToFilter('is_sent', array('null' => true))
		                ->addFieldToFilter('campaign_id', array('notnull' => true));
		$emailCollection->getSelect()->order('campaign_id');
		return $emailCollection;
	}
}