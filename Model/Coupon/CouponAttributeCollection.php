<?php

namespace Dotdigitalgroup\Email\Model\Coupon;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponAttribute as CouponAttributeResource;

class CouponAttributeCollection extends AbstractCollection
{
    /**
     * Constructor.
     */
    public function _construct()
    {
        $this->_init(
            CouponAttribute::class,
            CouponAttributeResource::class
        );
    }

    /**
     * Get active coupons for email.
     *
     * @deprecated Loads every matching row and column, and callers relying on getLastItem() get a
     * non-deterministic result. Use
     * Dotdigitalgroup\Email\Model\ResourceModel\CouponAttribute::getLatestForEmailAndRule() when
     * you only need the most recent coupon.
     * @see CouponAttributeResource::getLatestForEmailAndRule()
     *
     * @param int $ruleId
     * @param string $email
     * @return CouponAttributeCollection
     */
    public function getActiveCouponsForEmail(int $ruleId, string $email)
    {
        return $this->addFieldToFilter('main_table.email', $email)
            ->addFieldToFilter('salesrule_coupon.rule_id', $ruleId)
            ->join(
                ['salesrule_coupon' => $this->getTable('salesrule_coupon')],
                'salesrule_coupon.coupon_id = main_table.salesrule_coupon_id'
            )
            ->load();
    }
}
