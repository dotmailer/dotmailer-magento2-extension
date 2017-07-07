<?php

namespace Dotdigitalgroup\Email\Plugin;

/**
 * Class RuleCollectionPlugin.
 *
 * Set validation for the coupon codes.
 */
class RuleCollectionPlugin
{

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $date;

    /**
     * RuleCollectionPlugin constructor.
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date
    )
    {
        $this->date = $date;
    }

    /**
     * @param $subject
     * @param $result
     * @param $websiteId
     * @param $customerGroupId
     * @param $couponCode
     * @return mixed
     */
    public function afterSetValidationFilter($subject, $result, $websiteId, $customerGroupId, $couponCode)
    {
        $now = $this->date->date()->format('Y-m-d');
        $select = $subject->getSelect();

        if (! empty($couponCode)) {
            $select->where('(rule_coupons.expiration_date IS NULL) AND
                     (to_date is null or to_date >= ?)
                    OR
                     (rule_coupons.expiration_date IS NOT NULL) AND
                     (rule_coupons.expiration_date >= ?) ', $now);
        }
        $select->where(
            '(main_table.to_date IS NULL) OR (main_table.to_date >= ?)',
            $now
        );

        return $result;
    }

}
