<?php

namespace Dotdigitalgroup\Email\Model\Coupon;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponAttribute as CouponAttributeResource;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface;

class CouponAttributeCollection extends AbstractCollection
{
    public function _construct()
    {
        $this->_init(
            CouponAttribute::class,
            CouponAttributeResource::class
        );
    }

    /**
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
