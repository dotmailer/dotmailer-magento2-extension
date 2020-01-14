<?php

namespace Dotdigitalgroup\Email\Api;

use Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface;

interface CouponAttributeRepositoryInterface
{
    /**
     * @param int $id
     * @return \Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * @param \Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface $coupon
     * @return \Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface
     */
    public function save(CouponAttributeInterface $coupon);
}
