<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Rules;

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
            'Dotdigitalgroup\Email\Model\Rules',
            'Dotdigitalgroup\Email\Model\ResourceModel\Rules'
        );
    }

    /**
     * Reset collection.
     *
     * @return $this
     */
    public function reset()
    {
        $this->_reset();

        return $this;
    }
}
