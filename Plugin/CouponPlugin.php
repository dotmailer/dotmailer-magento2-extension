<?php

namespace Dotdigitalgroup\Email\Plugin;

/**
 * Class CouponPlugin - ignore to change the expiration day for dotmailer coupon codes.
 *
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class CouponPlugin
{
    /**
     * CouponPlugin constructor.
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon $subject
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon $result
     * @param \Magento\SalesRule\Model\Rule $rule
     *
     * @return \Magento\SalesRule\Model\Rule
     */
    public function afterUpdateSpecificCoupons(
        \Magento\SalesRule\Model\ResourceModel\Coupon $subject,
        $result,
        \Magento\SalesRule\Model\Rule $rule = null
    ) {
        if ($rule) {
            //update the generated and the expiration date
            $rule->setData('expiration_date', null);
            $rule->setData('generated_by_dotmailer', '1');
            $subject->save($rule);
        }

        return $rule;
    }
}
