<?php

namespace Dotdigitalgroup\Email\Model\Coupon;

use Dotdigitalgroup\Email\Api\Data\CouponAttributeInterface;
use Dotdigitalgroup\Email\Api\CouponAttributeRepositoryInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\CouponAttribute as CouponAttributeResource;
use Magento\Framework\Exception\NoSuchEntityException;

class CouponAttributeRepository implements CouponAttributeRepositoryInterface
{
    /**
     * @var CouponAttributeFactory
     */
    private $couponAttributeFactory;

    /**
     * @var CouponAttributeResource
     */
    private $couponAttributeResource;

    /**
     * CouponAttributeRepository constructor.
     * @param CouponAttributeFactory $couponAttributeFactory
     * @param CouponAttributeResource $couponAttributeResource
     */
    public function __construct(
        CouponAttributeFactory $couponAttributeFactory,
        CouponAttributeResource $couponAttributeResource
    ) {
        $this->couponAttributeFactory = $couponAttributeFactory;
        $this->couponAttributeResource = $couponAttributeResource;
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
        $this->couponAttributeResource->load(
            $couponAttribute,
            $id,
            CouponAttributeInterface::SALESRULE_COUPON_ID
        );
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
        $this->couponAttributeResource->save($coupon);
        return $coupon;
    }
}
