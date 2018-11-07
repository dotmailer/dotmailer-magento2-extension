<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\FailedAuth;

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
            \Dotdigitalgroup\Email\Model\FailedAuth::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\FailedAuth::class
        );
    }

    /**
     * @param int $storeId
     * @return $this
     */
    public function loadByStoreId($storeId)
    {
        $this->addFieldToFilter('store_id', $storeId);

        return $this;
    }
}
