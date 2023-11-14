<?php

namespace Dotdigitalgroup\Email\Api;

use Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface;

interface CouponAttributeRepositoryInterface
{
    /**
     * Fetch coupon attribute by id.
     *
     * @param int $id
     * @return \Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * Save coupon attribute.
     *
     * @param \Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface $coupon
     * @return \Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface
     */
    public function save(CouponAttributeInterface $coupon);
}
