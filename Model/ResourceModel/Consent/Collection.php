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
     * @return null
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Consent::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Consent::class
        );
    }

    /**
     * Load consent by contact id.
     *
     * @param int $contactId
     *
     * @return $this
     */
    public function loadByEmailContactId($contactId)
    {
        $this->addFieldToFilter('email_contact_id', $contactId);

        return $this;
    }
}
