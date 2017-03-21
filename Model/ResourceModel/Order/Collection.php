<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Order;

class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'email_order_id';

    /**
     * Initialize resource collection.
     */
    public function _construct()
    {
        $this->_init(
            'Dotdigitalgroup\Email\Model\Order',
            'Dotdigitalgroup\Email\Model\ResourceModel\Order'
        );
    }
}
