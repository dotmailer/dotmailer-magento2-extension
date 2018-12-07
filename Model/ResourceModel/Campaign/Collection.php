<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Campaign;

class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize resource collection.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Campaign::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Campaign::class
        );
    }

    /**
     * Get campaign by quote id.
     *
     * @param int $quoteId
     * @param int $storeId
     *
     * @return \Dotdigitalgroup\Email\Model\Campaign|boolean
     */
    public function loadByQuoteId($quoteId, $storeId)
    {
        $collection = $this->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('store_id', $storeId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }

    /**
     * Get campaign collection.
     *
     * @param array $storeIds
     * @param int $sendStatus
     * @param bool $sendIdCheck
     *
     * @return \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\Collection
     */
    public function getEmailCampaignsByStoreIds($storeIds, $sendStatus = 0, $sendIdCheck = false)
    {
        $campaignCollection = $this->addFieldToFilter('send_status', $sendStatus)
            ->addFieldToFilter('campaign_id', ['notnull' => true])
            ->addFieldToFilter('store_id', ['in' => $storeIds]);

        //check for send id
        if ($sendIdCheck) {
            $campaignCollection->addFieldToFilter('send_id', ['notnull' => true])
                ->getSelect()
                ->group('send_id');
        } else {
            $campaignCollection->getSelect()
                ->order('campaign_id');
        }

        $campaignCollection->getSelect()
            ->limit(\Dotdigitalgroup\Email\Model\Sync\Campaign::SEND_EMAIL_CONTACT_LIMIT);

        return $campaignCollection;
    }

    /**
     * Get collection by event.
     *
     * @param string $event
     *
     * @return $this
     */
    public function getCollectionByEvent($event)
    {
        return $this->addFieldToFilter('event_name', $event);
    }

    /**
     * Get number of campaigns for contact by interval.
     *
     * @param string  $email
     * @param array $updated
     *
     * @return int
     */
    public function getNumberOfCampaignsForContactByInterval($email, $updated)
    {
        return $this->addFieldToFilter('email', $email)
            ->addFieldToFilter('event_name', 'Lost Basket')
            ->addFieldToFilter('sent_at', $updated)
            ->count();
    }

    /**
     * @param string $email
     *
     * @return int
     */
    public function getNumberOfAcCampaignsWithStatusProcessingExistForContact($email)
    {
        return $this->addFieldToFilter('email', $email)
            ->addFieldToFilter('event_name', \Dotdigitalgroup\Email\Model\Campaign::CAMPAIGN_EVENT_LOST_BASKET)
            ->addFieldToFilter('send_status', \Dotdigitalgroup\Email\Model\Campaign::PROCESSING)
            ->getSize();
    }
}
