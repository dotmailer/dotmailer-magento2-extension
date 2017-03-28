<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Wishlist;

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
            'Dotdigitalgroup\Email\Model\Wishlist',
            'Dotdigitalgroup\Email\Model\ResourceModel\Wishlist'
        );
    }

    /**
     * Join the customer email and store id.
     * @return \Magento\Framework\DB\Select
     */
    public function joinLeftCustomer()
    {
        return $this->getSelect()
            ->joinLeft([
                'c' => $this->_resource->getTable('customer_entity')
            ], 'c.entity_id = customer_id', ['email', 'store_id']);
    }
}
