<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Automation;

/**
 * Class Collection
 * @package Dotdigitalgroup\Email\Model\ResourceModel\Automation
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id'; //@codingStandardsIgnoreLine

    /**
     * Initialize resource collection.
     */
    public function _construct() //@codingStandardsIgnoreLine
    {
        $this->_init(
            'Dotdigitalgroup\Email\Model\Automation',
            'Dotdigitalgroup\Email\Model\ResourceModel\Automation'
        );
    }
}
