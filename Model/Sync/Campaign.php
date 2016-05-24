<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Campaign
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var
     */
    protected $_storeManger;
    /**
     * @var \Dotdigitalgroup\Email\Model\Resource\Campaign\CollectionFactory
     */
    protected $_campaignCollection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Campaign constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Resource\Campaign\CollectionFactory $campaignFactory
     * @param \Dotdigitalgroup\Email\Helper\Data                               $data
     * @param \Magento\Store\Model\StoreManagerInterface                       $storeManagerInterface
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Resource\Campaign\CollectionFactory $campaignFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->_helper = $data;
        $this->_campaignCollection = $campaignFactory;
        $this->_storeManager = $storeManagerInterface;
    }

    /**
     * Sending the campaigns.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendCampaigns()
    {
        //grab the emails not send
        $emailsToSend = $this->_getEmailCampaigns();

        foreach ($emailsToSend as $campaign) {
            $email = $campaign->getEmail();
            $storeId = $campaign->getStoreId();
            $campaignId = $campaign->getCampaignId();
            $store = $this->_storeManager->getStore($storeId);
            $websiteId = $store->getWebsiteId();
            try {
                //campaigns id not found
                if (!$campaignId) {
                    $campaign->setMessage('Missing campaign id: '.$campaignId)
                        ->setIsSent(1)
                        ->save();
                    continue;
                    //email not found
                } elseif (!$email) {
                    $campaign->setMessage('Missing email : '.$email)
                        ->setIsSent(1)
                        ->save();
                    continue;
                }
                $client = $this->_helper->getWebsiteApiClient($websiteId);
                $contactId = $this->_helper->getContactId(
                    $campaign->getEmail(), $websiteId
                );
                if (is_numeric($contactId)) {
                    $response = $client->postCampaignsSend(
                        $campaignId, [$contactId]
                    );
                    //campaign not send, save message
                    if (isset($response->message)) {
                        //update  the failed to send email message
                        $campaign->setMessage($response->message)
                            ->setIsSent(1)
                            ->save();
                    }
                    //record susccess
                    $campaign->setIsSent(1)
                        ->setMessage(null)
                        ->setSentAt(gmdate('Y-m-d H:i:s'))
                        ->save();
                } else {
                    //update  the failed to send email message- error message from post contact
                    $campaign->setContactMessage($contactId)
                        ->setIsSent(1)
                        ->save();
                }
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($e->getMessage())
                );
            }
        }
    }

    /**
     * Get pending campaigns.
     * 
     * @return mixed
     */
    protected function _getEmailCampaigns()
    {
        $emailCollection = $this->_campaignCollection->create()
            ->addFieldToFilter('is_sent', ['null' => true])
            ->addFieldToFilter('campaign_id', ['notnull' => true]);
        $emailCollection->getSelect()->order('campaign_id');

        return $emailCollection;
    }
}
