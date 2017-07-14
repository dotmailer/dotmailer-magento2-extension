<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Contact;

/**
 * Class Collection
 * @package Dotdigitalgroup\Email\Model\ResourceModel\Contact
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'email_contact_id'; //@codingStandardsIgnoreLine

    /**
     * Initialize resource collection.
     */
    public function _construct() //@codingStandardsIgnoreLine
    {
        $this->_init(
            'Dotdigitalgroup\Email\Model\Contact',
            'Dotdigitalgroup\Email\Model\ResourceModel\Contact'
        );
    }
}
