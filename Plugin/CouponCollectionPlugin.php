<?php

namespace Dotdigitalgroup\Email\Plugin;

use Magento\SalesRule\Model\ResourceModel\Coupon\Collection;

class CouponCollectionPlugin
{
    /**
     * Join email_coupon_attribute table
     *
     * @param Collection $collection
     * @return Collection
     */
    public function afterAddRuleToFilter(Collection $collection)
    {
        $collection->getSelect()
            ->joinLeft(
                ['eca' => $collection->getTable('email_coupon_attribute')],
                'eca.salesrule_coupon_id = main_table.coupon_id'
            );

        return $collection;
    }
}
