<?php

namespace Dotdigitalgroup\Email\Model\Sync;

/**
 * Send email campaings.
 */
class Campaign implements SyncInterface
{
    //single call contact limit
    const SEND_EMAIL_CONTACT_LIMIT = 10;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory
     */
    private $campaignCollection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $salesOrderFactory;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    private $websiteFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign
     */
    private $campaignResourceModel;

    /**
     * Campaign constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $campaignFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $data
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Sales\Model\OrderFactory $salesOrderFactory
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign $campaignResourceModel
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
     *
     * @return null
     */
    public function sendCampaigns()
    {
        /** @var \Magento\Store\Api\Data\WebsiteInterface $website */
        foreach ($this->storeManager->getWebsites(true) as $website) {
            $storeIds = $website->getStoreIds();

            //check send status for processing
            $this->_checkSendStatus($website, $storeIds);
            //start send process

            $emailsToSend = $this->_getEmailCampaigns($storeIds);
            $campaignsToSend = $this->getCampaignsToSend($emailsToSend, $website);
            $this->sendCampaignsViaEngagementCloud($campaignsToSend);
        }
    }

    /**
     * @inheritdoc
     */
    public function sync(\DateTime $from = null)
    {
        $this->sendCampaigns();
    }


    /**
     * @param int $website
     * @param array $storeIds
     *
     * @return null
     */
    public function _checkSendStatus($website, $storeIds)
    {
        $this->expireExpiredCampaigns($storeIds);
        $campaigns = $this->_getEmailCampaigns(
            $storeIds,
            \Dotdigitalgroup\Email\Model\Campaign::PROCESSING,
            true
        );
        foreach ($campaigns as $campaign) {
            $client = $this->helper->getWebsiteApiClient($website);
            $response = $client->getSendStatus($campaign->getSendId());
            if (isset($response->message) || $response->status == 'Cancelled') {
                $message = isset($response->message) ? $response->message : $response->status;
                $this->campaignResourceModel->setMessageWithSendId($campaign->getSendId(), $message);
            } elseif ($response->status == 'Sent') {
                $this->campaignResourceModel->setSent(
                    $campaign->getSendId(),
                    $response->sendDate
                );
            }
        }
    }

    /**
     * @param array $storeIds
     */
    private function expireExpiredCampaigns($storeIds)
    {
        $expiredCampaigns = $this->campaignCollection->create()
            ->getExpiredEmailCampaignsByStoreIds($storeIds);
        $ids = $expiredCampaigns->getColumnValues('id');

        if (! empty($ids)) {
            $this->campaignResourceModel->expireCampaigns($ids);
        }
    }

    /**
     * Get campaigns to send.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\Collection $emailsToSend
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     *
     * @return array
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
            if ($client && $this->isCampaignValid($campaign)) {
                $campaignsToSend[$campaignId]['client'] = $client;
                $contact = $this->helper->getContact(
                    $campaign->getEmail(),
                    $websiteId
                );
                if ($contact && isset($contact->id)) {
                    //update data fields
                    if ($campaign->getEventName() == 'Order Review') {
                        $this->updateDataFieldsForORderReviewCampaigns($campaign, $websiteId, $client, $email);
                    } elseif ($campaign->getEventName() ==
                        \Dotdigitalgroup\Email\Model\Campaign::CAMPAIGN_EVENT_LOST_BASKET
                    ) {
                        $campaignCollection = $this->campaignCollection->create();
                        // If AC campaigns found with status processing for given email then skip for current cron run
                        if ($campaignCollection->getNumberOfAcCampaignsWithStatusProcessingExistForContact($email)) {
                            continue;
                        }
                        $this->helper->updateLastQuoteId($campaign->getQuoteId(), $email, $websiteId);
                    }

                    $campaignsToSend[$campaignId]['contacts'][] = $contact->id;
                    $campaignsToSend[$campaignId]['ids'][] = $campaign->getId();
                } else {
                    //update the failed to send email message error message
                    $campaign->setSendStatus(\Dotdigitalgroup\Email\Model\Campaign::FAILED)
                        ->setMessage('Send not permitted. Contact is suppressed.');
                    $this->campaignResourceModel->saveItem($campaign);
                }
            }
        }

        return $campaignsToSend;
    }

    /**
     * Check if campaign item is valid
     *
     * @param \Dotdigitalgroup\Email\Model\Campaign $campaign
     *
     * @return bool
     */
    private function isCampaignValid($campaign)
    {
        if (! $campaign->getCampaignId()) {
            $campaign->setMessage('Missing campaign id: ' . $campaign->getCampaignId())
                ->setSendStatus(\Dotdigitalgroup\Email\Model\Campaign::FAILED);
            $this->campaignResourceModel->saveItem($campaign);
            return false;
        } elseif (! $campaign->getEmail()) {
            $campaign->setMessage('Missing email')
                ->setSendStatus(\Dotdigitalgroup\Email\Model\Campaign::FAILED);
            $this->campaignResourceModel->saveItem($campaign);
            return false;
        }
        return true;
    }

    /**
     * Send campaigns.
     *
     * @param array $campaignsToSend
     *
     * @return null
     */
    private function sendCampaignsViaEngagementCloud($campaignsToSend)
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
     * Get campaign collection.
     *
     * @param array $storeIds
     * @param int $sendStatus
     * @param bool $sendIdCheck
     *
     * @return mixed
     */
    public function _getEmailCampaigns($storeIds, $sendStatus = 0, $sendIdCheck = false)
    {
        return $this->campaignCollection->create()
            ->getEmailCampaignsByStoreIds($storeIds, $sendStatus, $sendIdCheck);
    }

    /**
     * @param \Dotdigitalgroup\Email\Model\Campaign $campaign
     * @param int $websiteId
     * @param \Dotdigitalgroup\Email\Model\Apiconnector\Client $client
     * @param string $email
     *
     * @return null
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
