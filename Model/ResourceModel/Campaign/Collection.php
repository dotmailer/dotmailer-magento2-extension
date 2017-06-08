<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Campaign;

class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';
    /**
     * Initialize resource collection.
     */
    public function _construct()
    {
        $this->_init(
            'Dotdigitalgroup\Email\Model\Campaign',
            'Dotdigitalgroup\Email\Model\ResourceModel\Campaign'
        );
    }

    /**
     * Get campaign by quote id.
     *
     * @param int $quoteId
     * @param int $storeId
     *
     * @return mixed
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
     * Get campaign collection
     *
     * @param $storeIds
     * @param $sendStatus
     * @param $sendIdCheck
     * @return mixed
     */
    public function getEmailCampaignsByStoreIds($storeIds, $sendStatus = 0, $sendIdCheck = false)
    {
        $emailCollection = $this->addFieldToFilter('send_status', $sendStatus)
            ->addFieldToFilter('campaign_id', ['notnull' => true])
            ->addFieldToFilter('store_id', ['in' => $storeIds]);

        //check for send id
        if ($sendIdCheck) {
            $emailCollection->addFieldToFilter('send_id', ['notnull' => true])
                ->getSelect()
                ->group('send_id');
        } else {
            $emailCollection->getSelect()
                ->order('campaign_id');
        }

        $emailCollection->getSelect()
            ->limit(\Dotdigitalgroup\Email\Model\Sync\Campaign::SEND_EMAIL_CONTACT_LIMIT);

        return $emailCollection;
    }

    /**
     * Get collection by event
     *
     * @param $event
     * @return $this
     */
    public function getCollectionByEvent($event)
    {
        return $this->addFieldToFilter('event_name', $event);
    }

    /**
     * Get number of campaigns for contact by interval
     *
     * @param $email
     * @param $updated
     * @return int
     */
    public function getNumberOfCampaignsForContactByInterval($email, $updated)
    {
        return $this->addFieldToFilter('email', $email)
            ->addFieldToFilter('event_name', 'Lost Basket')
            ->addFieldToFilter('sent_at', $updated)
            ->count();
    }
}
