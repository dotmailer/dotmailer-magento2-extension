<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Consent;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize resource collection.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Consent::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Consent::class
        );
    }

    /**
     * Get most recent consent data by contact ids.
     *
     * This query does a join on itself to identify the rows with the most
     * recent consent_datetime. i.e. the aliases a and b refer to the same table.
     *
     * @param array $contactIds
     *
     * @return array
     */
    public function getMostRecentConsentDataByContactIds(array $contactIds)
    {
        $connection = $this->getResource()->getConnection();
        $select = $connection->select()
            ->from([
                'a' => $this->getMainTable(),
            ], [
                'a.*'
            ])
            ->joinLeft(
                ['b' => $this->getMainTable()],
                'a.email_contact_id = b.email_contact_id and a.consent_datetime < b.consent_datetime',
                []
            )
            ->where('b.consent_datetime IS NULL')
            ->where('a.email_contact_id IN (?)', $contactIds);

        return $connection->fetchAll($select);
    }
}
