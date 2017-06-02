<?php

namespace Dotdigitalgroup\Email\Model\Sync;

/**
 * Send email campaings.
 */
class Campaign
{
    //single call contact limit
    const SEND_EMAIL_CONTACT_LIMIT = 10;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var
     */
    public $storeManger;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory
     */
    public $campaignCollection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    public $salesOrderFactory;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    public $websiteFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign
     */
    public $campaignResourceModel;

    /**
     * Campaign constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Sales\Model\OrderFactory $salesOrderFactory
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignFactory,
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Sales\Model\OrderFactory $salesOrderFactory,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign $campaignResourceModel
    ) {
        $this->campaignResourceModel = $campaignResourceModel;
        $this->websiteFactory        = $websiteFactory;
        $this->helper                = $data;
        $this->campaignCollection    = $campaignFactory;
        $this->storeManager          = $storeManagerInterface;
        $this->salesOrderFactory     = $salesOrderFactory;
    }

    /**
     * Sending the campaigns
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendCampaigns()
    {
        foreach ($this->storeManager->getWebsites(true) as $website) {
            //check send status for processing
            $this->_checkSendStatus($website);
            //start send process
            $website = $this->websiteFactory->create();
            $storeIds = $website->getResource()
                ->load($website, $website->getId())
                ->getStoreIds();
            $emailsToSend = $this->_getEmailCampaigns($storeIds);
            $campaignsToSend = $this->getCampaignsToSend($emailsToSend, $website);
            $this->sendCampaignsViaDotmailer($campaignsToSend);
        }
    }

    /**
     * @param $website
     */
    public function _checkSendStatus($website)
    {
        $website = $this->websiteFactory->create();
        $storeIds = $website->getResource()
            ->load($website, $website->getId())
            ->getStoreIds();
        $campaigns = $this->_getEmailCampaigns(
            $storeIds,
            \Dotdigitalgroup\Email\Model\Campaign::PROCESSING,
            true
        );
        foreach ($campaigns as $campaign) {
            $client = $this->helper->getWebsiteApiClient($website);
            $response = $client->getSendStatus($campaign->getSendId());
            if (isset($response->message)) {
                //update  the failed to send email message
                $this->campaignResourceModel->setMessageWithSendId($campaign->getSendId(), $response->message);
            } elseif ($response->status == 'Sent') {
                $this->campaignResourceModel->setSent($campaign->getSendId());
            }
        }
    }

    /**
     * Get campaigns to send
     *
     * @param $emailsToSend
     * @param $website
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCampaignsToSend($emailsToSend, $website)
    {
        $campaignsToSend = [];
        foreach ($emailsToSend as $campaign) {
            $email = $campaign->getEmail();
            $campaignId = $campaign->getCampaignId();
            $websiteId = $website->getId();
            $client = false;
            if ($this->helper->isEnabled($websiteId)) {
                $client = $this->helper->getWebsiteApiClient($websiteId);
            }
            //Only if valid client is returned
            if ($client) {
                if (!$campaignId) {
                    $campaign->setMessage('Missing campaign id: ' . $campaignId)
                        ->setSendStatus(\Dotdigitalgroup\Email\Model\Campaign::FAILED);
                    $this->campaignResourceModel->saveItem($campaign);
                    continue;
                } elseif (!$email) {
                    $campaign->setMessage('Missing email')
                        ->setSendStatus(\Dotdigitalgroup\Email\Model\Campaign::FAILED);
                    $this->campaignResourceModel->saveItem($campaign);
                    continue;
                }
                $campaignsToSend[$campaignId]['client'] = $client;
                try {
                    $contactId = $this->helper->getContactId(
                        $campaign->getEmail(),
                        $websiteId
                    );
                    if (is_numeric($contactId)) {
                        //update data fields for order review camapigns
                        if ($campaign->getEventName() == 'Order Review') {
                            $this->updateDataFieldsForORderReviewCampaigns($campaign, $websiteId, $client, $email);
                        }
                        $campaignsToSend[$campaignId]['contacts'][] = $contactId;
                        $campaignsToSend[$campaignId]['ids'][] = $campaign->getId();
                    } else {
                        //update the failed to send email message error message
                        $campaign->setSendStatus(\Dotdigitalgroup\Email\Model\Campaign::FAILED)
                            ->setMessage('Send not permitted. Contact is suppressed.');
                        $this->campaignResourceModel->saveItem($campaign);
                    }
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($e->getMessage())
                    );
                }
            }
        }
        return $campaignsToSend;
    }

    /**
     * Send campaigns
     *
     * @param $campaignsToSend
     */
    private function sendCampaignsViaDotmailer($campaignsToSend)
    {
        foreach ($campaignsToSend as $campaignId => $data) {
            if (isset($data['contacts']) && isset($data['client'])) {
                $contacts = $data['contacts'];
                /** @var \Dotdigitalgroup\Email\Model\Apiconnector\Client $client */
                $client = $data['client'];
                $response = $client->postCampaignsSend(
                    $campaignId,
                    $contacts
                );
                if (isset($response->message)) {
                    //update  the failed to send email message
                    $this->campaignResourceModel->setMessage($data['ids'], $response->message);
                } elseif (isset($response->id)) {
                    $this->campaignResourceModel->setProcessing($data['ids'], $response->id);
                } else {
                    //update  the failed to send email message
                    $this->campaignResourceModel->setMessage($data['ids'], 'No send id returned.');
                }
            }
        }
    }

    /**
     * Get campaign collection
     *
     * @param $storeIds
     * @param $sendStatus
     * @param $sendIdCheck
     * @return mixed
     */
    public function _getEmailCampaigns($storeIds, $sendStatus = 0, $sendIdCheck = false)
    {
        return $this->campaignCollection->create()
            ->getEmailCampaignsByStoreIds($storeIds, $sendStatus, $sendIdCheck);
    }

    /**
     * @param $campaign
     * @param $websiteId
     * @param $client
     * @param $email
     */
    private function updateDataFieldsForORderReviewCampaigns($campaign, $websiteId, $client, $email)
    {
        $order = $this->salesOrderFactory->create()->loadByIncrementId(
            $campaign->getOrderIncrementId()
        );

        if ($lastOrderId = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID,
            $websiteId
        )
        ) {
            $data[] = [
                'Key' => $lastOrderId,
                'Value' => $order->getId(),
            ];
        }
        if ($orderIncrementId = $this->helper->getWebsiteConfig(
            \Dotdigitalgroup\Email\Helper\Config::
            XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_INCREMENT_ID,
            $websiteId
        )
        ) {
            $data[] = [
                'Key' => $orderIncrementId,
                'Value' => $order->getIncrementId(),
            ];
        }

        if (!empty($data)) {
            //update data fields
            $client->updateContactDatafieldsByEmail(
                $email,
                $data
            );
        }
    }
}
