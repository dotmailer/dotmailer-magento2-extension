<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Consent;

use Dotdigitalgroup\Email\Setup\SchemaInterface;

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

    /**
     * Get consent records to sync.
     *
     * @param int $pageSize
     * @param int $offset
     * @param mixed $websiteId
     * @return Collection
     */
    public function getConsentRecordsToSync(int $pageSize, int $offset, $websiteId)
    {
        $collection = $this->addFieldToSelect('*')
            ->addFieldToFilter('consent_imported', 0);

        $collection->getSelect()
            ->joinLeft(
                ['email_contact' => $this->getTable(SchemaInterface::EMAIL_CONTACT_TABLE)],
                'main_table.email_contact_id = email_contact.email_contact_id',
                ['email', 'website_id']
            )->where('website_id = ?', $websiteId)
            ->limit($pageSize, $offset);

        return $collection;
    }

    /**
     * Get available websites to sync.
     *
     * @return array
     */
    public function getWebsitesToSync()
    {
        $connection = $this->getResource()->getConnection();
        $select = $connection->select()
            ->from(
                ['email_contact' => $this->getTable(SchemaInterface::EMAIL_CONTACT_TABLE)],
                ['email_contact.website_id']
            )->joinLeft(
                ['email_contact_consent' => $this->getMainTable()],
                'email_contact_consent.email_contact_id = email_contact.email_contact_id',
                []
            )
            ->where('email_contact_consent.consent_imported = (?)', 0)
            ->group('website_id');

        return $connection->fetchCol($select);
    }
}
