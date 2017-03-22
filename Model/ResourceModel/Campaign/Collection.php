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
}
