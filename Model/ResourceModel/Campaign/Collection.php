<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Campaign;

/**
 * Class Collection
 * @package Dotdigitalgroup\Email\Model\ResourceModel\Campaign
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id'; //@codingStandardsIgnoreLine
    /**
     * Initialize resource collection.
     */
    public function _construct() //@codingStandardsIgnoreLine
    {
        $this->_init(
            'Dotdigitalgroup\Email\Model\Campaign',
            'Dotdigitalgroup\Email\Model\ResourceModel\Campaign'
        );
    }
}
