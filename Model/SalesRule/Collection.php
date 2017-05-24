<?php

namespace Dotdigitalgroup\Email\Model\SalesRule;

class Collection extends \Magento\SalesRule\Model\ResourceModel\Rule\Collection
{
    /**
     * Filter collection by specified website, customer group, coupon code, date.
     * Filter collection to use only active rules.
     * Involved sorting by sort_order column.
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string $couponCode
     * @param string|null $now
     * @param \Magento\Quote\Model\Quote\Address $address allow extensions to further
     *                                                    filter out rules based on quote address
     * @use $this->addWebsiteGroupDateFilter()
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @return $this
     */
    public function setValidationFilter(
        $websiteId,
        $customerGroupId,
        $couponCode = '',
        $now = null,
        \Magento\Quote\Model\Quote\Address $address = null
    ) {
        if (!$this->getFlag('validation_filter')) {
            if ($now === null) {
                $now = $this->_date->date()->format('Y-m-d');
            }

            /* We need to overwrite joinLeft if coupon is applied */
            $this->getSelect()->reset();
            $this->getSelect()->from(['main_table' => $this->getMainTable()]);

            $this->addWebsiteGroupDateFilter($websiteId, $customerGroupId, $now);
            $select = $this->getSelect();

            $connection = $this->getConnection();
            if (! empty($couponCode)) {
                $select->joinLeft(
                    ['rule_coupons' => $this->getTable('salesrule_coupon')],
                    $connection->quoteInto(
                        'main_table.rule_id = rule_coupons.rule_id AND main_table.coupon_type != ?',
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
                    ),
                    ['code']
                );

                $orWhereConditions = [
                    $connection->quoteInto(
                        'main_table.coupon_type = ? ',
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
                    ),
                    $connection->quoteInto(
                        '(main_table.coupon_type = ? AND rule_coupons.type = 0)',
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO
                    ),
                    $connection->quoteInto(
                        '(main_table.coupon_type = ? AND main_table.use_auto_generation = 1 AND rule_coupons.type = 1)',
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
                    ),
                    $connection->quoteInto(
                        '(main_table.coupon_type = ? AND main_table.use_auto_generation = 0 AND rule_coupons.type = 0)',
                        \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC
                    ),
                ];

                $andWhereConditions = [
                    $connection->quoteInto(
                        'rule_coupons.code = ?',
                        $couponCode
                    ),
                    $connection->quoteInto(
                        '(rule_coupons.expiration_date IS NULL OR rule_coupons.expiration_date >= ?)',
                        $this->_date->date()->format('Y-m-d')
                    ),
                ];

                $orWhereCondition = implode(' OR ', $orWhereConditions);
                $andWhereCondition = implode(' AND ', $andWhereConditions);

                $select->where('(' . $orWhereCondition . ') AND ' . $andWhereCondition);

                $select->where('(rule_coupons.expiration_date IS NULL) AND
                         (to_date is null or to_date >= ?)
                        OR
                         (rule_coupons.expiration_date IS NOT NULL) AND
                         (rule_coupons.expiration_date >= ?) ', $now);
            } else {
                $this->addFieldToFilter(
                    'main_table.coupon_type',
                    \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
                );
            }

            $select->where(
                '(main_table.to_date IS NULL) OR (main_table.to_date >= ?)',
                $now
            );

            $this->setOrder('sort_order', self::SORT_ORDER_ASC);
            $this->setFlag('validation_filter', true);
        }

        return $this;
    }

    /**
     * Filter collection by website(s), customer group(s) and date.
     * Filter collection to only active rules.
     * Sorting is not involved
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string|null $now
     * @use $this->addWebsiteFilter()
     * @return $this
     */
    public function addWebsiteGroupDateFilter($websiteId, $customerGroupId, $now = null)
    {
        if (!$this->getFlag('website_group_date_filter')) {
            if ($now === null) {
                $now = $this->_date->date()->format('Y-m-d');
            }

            $this->addWebsiteFilter($websiteId);

            $entityInfo = $this->_getAssociatedEntityInfo('customer_group');
            $connection = $this->getConnection();
            $this->getSelect()->joinInner(
                ['customer_group_ids' => $this->getTable($entityInfo['associations_table'])],
                $connection->quoteInto(
                    'main_table.' .
                    $entityInfo['rule_id_field'] .
                    ' = customer_group_ids.' .
                    $entityInfo['rule_id_field'] .
                    ' AND customer_group_ids.' .
                    $entityInfo['entity_id_field'] .
                    ' = ?',
                    (int)$customerGroupId
                ),
                []
            )
                ->where('from_date is null or from_date <= ?', $now);

            $this->addIsActiveFilter();

            $this->setFlag('website_group_date_filter', true);
        }

        return $this;
    }
}
