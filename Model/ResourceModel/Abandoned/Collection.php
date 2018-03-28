<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Abandoned;

class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
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
            \Dotdigitalgroup\Email\Model\Abandoned::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned::class
        );
    }
}
