<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\CouponJob;

use Dotdigitalgroup\Email\Model\ResourceModel\CouponJob;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Construct.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\CouponJob::class,
            CouponJob::class
        );
    }

    /**
     * Trigger Resource Model hydration for every item in the collection
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        foreach ($this->_items as $item) {
            $this->getResource()->afterLoad($item);
        }

        return $this;
    }
}
