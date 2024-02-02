<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Campaign as CampaignModel;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Contact\Patcher;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\StatusInterface as V3StatusInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Campaign\Collection;
use Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Send email campaigns.
 */
class Campaign implements SyncInterface
{
    //single call contact limit
    public const SEND_EMAIL_CONTACT_LIMIT = 10;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Patcher
     */
    private $patcher;

    /**
     * @var CollectionFactory
     */
    private $campaignCollection;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var OrderFactory
     */
    private $salesOrderFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign
     */
    private $campaignResourceModel;

    /**
     * Campaign constructor.
     *
     * @param CollectionFactory $campaignFactory
     * @param Data $data
     * @param Logger $logger
     * @param Patcher $patcher
     * @param StoreManagerInterface $storeManagerInterface
     * @param OrderFactory $salesOrderFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign $campaignResourceModel
     */
    public function __construct(
        CollectionFactory $campaignFactory,
        Data $data,
        Logger $logger,
        Patcher $patcher,
        StoreManagerInterface $storeManagerInterface,
        OrderFactory $salesOrderFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign $campaignResourceModel
    ) {
        $this->campaignResourceModel = $campaignResourceModel;
        $this->helper = $data;
        $this->logger = $logger;
        $this->patcher = $patcher;
        $this->campaignCollection = $campaignFactory;
        $this->storeManager = $storeManagerInterface;
        $this->salesOrderFactory = $salesOrderFactory;
    }

    /**
     * @inheritdoc
     */
    public function sync(\DateTime $from = null)
    {
        $this->sendCampaigns();
    }

    /**
     * Sending the campaigns
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return void
     */
    private function sendCampaigns()
    {
        foreach ($this->storeManager->getWebsites(true) as $website) {
            /** @var \Magento\Store\Model\Website $website */
            $storeIds = $website->getStoreIds();

            //check send status for processing
            $this->_checkSendStatus($website->getId(), $storeIds);
            //start send process

            $emailsToSend = $this->_getEmailCampaigns($storeIds);
            $campaignsToSend = $this->getCampaignsToSend($emailsToSend, $website);
            $this->sendCampaignsViaEngagementCloud($campaignsToSend);
        }
    }

    /**
     * Check send status.
     *
     * Expires old campaigns and updates 'processing' ones.
     *
     * @param int $websiteId
     * @param array $storeIds
     *
     * @return void
     * @throws LocalizedException
     */
    public function _checkSendStatus($websiteId, $storeIds)
    {
        $this->expireExpiredCampaigns($storeIds);
        $campaigns = $this->_getEmailCampaigns(
            $storeIds,
            CampaignModel::PROCESSING,
            true
        );
        foreach ($campaigns as $campaign) {
            $client = $this->helper->getWebsiteApiClient($websiteId);
            $response = $client->getSendStatus($campaign->getSendId());
            if (isset($response->message) || $response->status == 'Cancelled') {
                $message = $response->message ?? $response->status;
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
     * Expire campaigns.
     *
     * @param array $storeIds
     *
     * @throws LocalizedException
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
     * @param Collection $emailsToSend
     * @param WebsiteInterface $website
     *
     * @return array
     * @throws AlreadyExistsException|\Http\Client\Exception
     */
    private function getCampaignsToSend($emailsToSend, $website)
    {
        $campaignsToSend = [];
        foreach ($emailsToSend as $campaign) {
            $email = $campaign->getEmail();
            $campaignId = $campaign->getCampaignId();
            $websiteId = (int) $website->getId();
            $client = false;
            if ($this->helper->isEnabled($websiteId)) {
                $client = $this->helper->getWebsiteApiClient($websiteId);
            }
            //Only if valid client is returned
            if ($client && $this->isCampaignValid($campaign)) {
                $campaignsToSend[$campaignId]['client'] = $client;
                try {
                    $contact = $this->patcher->getOrCreateContactByEmail(
                        $campaign->getEmail(),
                        $websiteId,
                        (int) $campaign->getStoreId()
                    );
                } catch (ResponseValidationException $e) {
                    $this->logger->error(
                        sprintf(
                            '%s: %s',
                            'Error getting contact in campaign sync',
                            $e->getMessage()
                        ),
                        [$e->getDetails()]
                    );
                    continue;
                } catch (\Exception $e) {
                    $campaign->setSendStatus(CampaignModel::FAILED)
                        ->setMessage('Could not create contact.');
                    $this->campaignResourceModel->saveItem($campaign);
                    $this->logger->error((string) $e);
                    continue;
                }

                if ($contact->getChannelProperties()->getEmail()->getStatus() === V3StatusInterface::SUPPRESSED) {
                    $campaign->setSendStatus(CampaignModel::FAILED)
                        ->setMessage('Send not permitted. Contact is suppressed.');
                    $this->campaignResourceModel->saveItem($campaign);
                    continue;
                }

                //update data fields
                if ($campaign->getEventName() == CampaignModel::CAMPAIGN_EVENT_ORDER_REVIEW) {
                    $this->updateDataFieldsForOrderReviewCampaigns($campaign, $websiteId, $client, $email);
                } elseif ($campaign->getEventName() == CampaignModel::CAMPAIGN_EVENT_LOST_BASKET) {
                    $campaignCollection = $this->campaignCollection->create();
                    // If AC campaigns found with status processing for given email then skip for current cron run
                    if ($campaignCollection->getNumberOfAcCampaignsWithStatusProcessingExistForContact($email)) {
                        continue;
                    }
                    $this->helper->updateLastQuoteId($campaign->getQuoteId(), $email, $websiteId);
                }

                $campaignsToSend[$campaignId]['contacts'][] = $contact->getContactId();
                $campaignsToSend[$campaignId]['ids'][] = $campaign->getId();
            }
        }

        return $campaignsToSend;
    }

    /**
     * Check if campaign item is valid
     *
     * @param CampaignModel $campaign
     *
     * @return bool
     * @throws AlreadyExistsException
     */
    private function isCampaignValid($campaign)
    {
        if (! $campaign->getCampaignId()) {
            $campaign->setMessage('Missing campaign id: ' . $campaign->getCampaignId())
                ->setSendStatus(CampaignModel::FAILED);
            $this->campaignResourceModel->saveItem($campaign);
            return false;
        } elseif (! $campaign->getEmail()) {
            $campaign->setMessage('Missing email')
                ->setSendStatus(CampaignModel::FAILED);
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
     * @return void
     * @throws LocalizedException
     */
    private function sendCampaignsViaEngagementCloud($campaignsToSend)
    {
        foreach ($campaignsToSend as $campaignId => $data) {
            if (isset($data['contacts']) && isset($data['client'])) {
                $contacts = $data['contacts'];
                /** @var Client $client */
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
     * Update data fields.
     *
     * @param CampaignModel $campaign
     * @param int $websiteId
     * @param Client $client
     * @param string $email
     *
     * @return void
     * @throws \Exception
     */
    private function updateDataFieldsForOrderReviewCampaigns($campaign, $websiteId, $client, $email)
    {
        $order = $this->salesOrderFactory->create()->loadByIncrementId(
            $campaign->getOrderIncrementId()
        );

        if ($lastOrderId = $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID,
            $websiteId
        )
        ) {
            $data[] = [
                'Key' => $lastOrderId,
                'Value' => $order->getId(),
            ];
        }
        if ($orderIncrementId = $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_INCREMENT_ID,
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
