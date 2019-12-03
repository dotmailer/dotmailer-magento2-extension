<?php

namespace Dotdigitalgroup\Email\Test\Integration;

use Dotdigitalgroup\Email\Model\Coupon\CouponAttributeCollection;
use Magento\SalesRule\Model\CouponRepository;
use Magento\TestFramework\ObjectManager;

trait RedeemsCoupons
{
    private function redeemCoupon(int $ruleId, string $email)
    {
        /** @var CouponAttributeCollection $couponAttributeCollection */
        $couponAttributeCollection = ObjectManager::getInstance()->create(CouponAttributeCollection::class);
        $couponAttribute = $couponAttributeCollection->getActiveCouponsForEmail($ruleId, $email)
            ->getFirstItem();

        /** @var CouponRepository $couponRepository */
        $couponRepository = ObjectManager::getInstance()->create(CouponRepository::class);
        $coupon = $couponRepository->getById($couponAttribute->getCouponId());
        $coupon->setTimesUsed(1);
        $couponRepository->save($coupon);
    }
}
