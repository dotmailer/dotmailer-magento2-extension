<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Importer;

/**
 * Class Collection
 * @package Dotdigitalgroup\Email\Model\ResourceModel\Importer
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'id'; //@codingStandardsIgnoreLine

    /**
     * Initialize resource collection.
     */
    public function _construct() //@codingStandardsIgnoreLine
    {
        $this->_init('Dotdigitalgroup\Email\Model\Importer', 'Dotdigitalgroup\Email\Model\ResourceModel\Importer');
    }

    /**
     * Reset collection.
     */
    public function reset()
    {
        $this->_reset();
    }
}
