<?php

namespace Dotdigitalgroup\Email\Model\Coupon;

use Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface;
use Dotdigitalgroup\Email\Api\CouponAttributeRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class CouponAttributeRepository implements CouponAttributeRepositoryInterface
{
    /**
     * @var CouponAttributeFactory
     */
    private $couponAttributeFactory;

    public function __construct(
        CouponAttributeFactory $couponAttributeFactory
    ) {
        $this->couponAttributeFactory = $couponAttributeFactory;
    }

    /**
     * Get by Id
     *
     * @param int $id
     * @return CouponAttribute
     * @throws NoSuchEntityException
     */
    public function getById($id)
    {
        $couponAttribute = $this->couponAttributeFactory->create();
        $couponAttribute->getResource()->load($couponAttribute, $id, CouponAttributeInterface::SALESRULE_COUPON_ID);
        if (! $couponAttribute->getId()) {
            throw new NoSuchEntityException(__('Unable to find coupon attribute with ID "%1"', $id));
        }
        return $couponAttribute;
    }

    /**
     * Save
     *
     * @param CouponAttributeInterface $coupon
     * @return CouponAttributeInterface
     */
    public function save(CouponAttributeInterface $coupon)
    {
        $coupon->getResource()->save($coupon);
        return $coupon;
    }
}
