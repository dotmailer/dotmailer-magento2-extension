<?php

namespace Dotdigitalgroup\Email\Model\Sync;

class Campaign
{
    //single call contact limit
    const SEND_EMAIL_CONTACT_LIMIT = 10;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    protected $_helper;
    /**
     * @var
     */
    protected $_storeManger;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory
     */
    protected $_campaignCollection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_salesOrderFactory;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $_websiteFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign
     */
    protected $_campaignResourceModel;

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
        $this->_campaignResourceModel = $campaignResourceModel;
        $this->_websiteFactory = $websiteFactory;
        $this->_helper = $data;
        $this->_campaignCollection = $campaignFactory;
        $this->_storeManager = $storeManagerInterface;
        $this->_salesOrderFactory = $salesOrderFactory;
    }

    /**
     * Sending the campaigns
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sendCampaigns()
    {
        foreach ($this->_storeManager->getWebsites(true) as $website) {
            $storeIds = $this->_websiteFactory->create()->load($website->getId())->getStoreIds();
            $emailsToSend = $this->_getEmailCampaigns($storeIds);
            $campaignsToSend = [];
            foreach ($emailsToSend as $campaign) {
                $email = $campaign->getEmail();
                $campaignId = $campaign->getCampaignId();
                $websiteId = $website->getId();
                $client = false;
                if ($this->_helper->isEnabled($websiteId)) {
                    $client = $this->_helper->getWebsiteApiClient($websiteId);
                }
                //Only if valid client is returned
                if ($client) {
                    //@codingStandardsIgnoreStart
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
                    //@codingStandardsIgnoreEnd
                    $campaignsToSend[$campaignId]['client'] = $client;
                    try {
                        $contactId = $this->_helper->getContactId(
                            $campaign->getEmail(), $websiteId
                        );
                        if (is_numeric($contactId)) {
                            //update data fields for order review camapigns
                            if ($campaign->getEventName() == 'Order Review') {
                                $order = $this->_salesOrderFactory->create()->loadByIncrementId(
                                    $campaign->getOrderIncrementId()
                                );

                                if ($lastOrderId = $this->_helper->getWebsiteConfig(
                                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_ID,
                                    $websiteId
                                )
                                ) {
                                    $data[] = [
                                        'Key' => $lastOrderId,
                                        'Value' => $order->getId(),
                                    ];
                                }
                                if ($orderIncrementId = $this->_helper->getWebsiteConfig(
                                    \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_CUSTOMER_LAST_ORDER_INCREMENT_ID,
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
                                        $email, $data
                                    );
                                }
                            }
                            $campaignsToSend[$campaignId]['contacts'][] = $contactId;
                        } else {
                            //update the failed to send email message error message from post contact
                            $campaign->setContactMessage($contactId)->setIsSent(1)
                                ->save();
                        }
                    } catch (\Exception $e) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __($e->getMessage())
                        );
                    }
                }
            }
            foreach ($campaignsToSend as $campaignId => $data) {
                if (isset($data['contacts']) && isset($data['client'])) {
                    $contacts = $data['contacts'];
                    $client = $data['client'];
                    $response = $client->postCampaignsSend(
                        $campaignId, $contacts
                    );
                    if (isset($response->message)) {
                        //update  the failed to send email message
                        $this->_campaignResourceModel->setMessage($campaignId, $response->message);
                    } else {
                        //Set sent with/without send id
                        if (isset($response->id)) {
                            $this->_campaignResourceModel->setSent($campaignId, $response->id);
                        } else {
                            $this->_campaignResourceModel->setSent($campaignId);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get pending campaigns.
     *
     * @param $storeIds
     * @return mixed
     */
    protected function _getEmailCampaigns($storeIds)
    {
        $emailCollection = $this->_campaignCollection->create()
            ->addFieldToFilter('is_sent', ['null' => true])
            ->addFieldToFilter('campaign_id', ['notnull' => true])
            ->addFieldToFilter('store_id', ['in' => $storeIds]);
        $emailCollection->getSelect()
            ->order('campaign_id')
            ->limit(self::SEND_EMAIL_CONTACT_LIMIT);

        return $emailCollection;
    }
}
