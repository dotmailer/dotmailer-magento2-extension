<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Api\CouponAttributeRepositoryInterface;
use Dotdigitalgroup\Email\Model\Coupon\CouponAttribute;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Api\Data\CouponExtensionFactory;
use Magento\SalesRule\Api\Data\CouponInterface;

class CouponPlugin
{
    /**
     * @var CouponAttributeRepositoryInterface
     */
    private $couponAttributeRepository;

    /**
     * @var CouponExtensionFactory
     */
    private $couponExtensionFactory;

    /**
     * @param CouponAttributeRepositoryInterface $couponAttributeRepository
     * @param CouponExtensionFactory $couponExtensionFactory
     */
    public function __construct(
        CouponAttributeRepositoryInterface $couponAttributeRepository,
        CouponExtensionFactory $couponExtensionFactory
    ) {
        $this->couponAttributeRepository = $couponAttributeRepository;
        $this->couponExtensionFactory = $couponExtensionFactory;
    }

    /**
     * After get by id.
     *
     * @param CouponRepositoryInterface $subject
     * @param CouponInterface $entity
     * @return CouponInterface
     */
    public function afterGetById(
        CouponRepositoryInterface $subject,
        CouponInterface $entity
    ) {
        try {
            $couponAttribute = $this->couponAttributeRepository->getById($entity->getCouponId());
        } catch (NoSuchEntityException $e) {
            return $entity;
        }

        $extensionAttributes = $entity->getExtensionAttributes()
            ?: $this->couponExtensionFactory->create();

        $extensionAttributes->setDdgExtensionAttributes($couponAttribute);
        $entity->setExtensionAttributes($extensionAttributes);

        return $entity;
    }

    /**
     * After save.
     *
     * @param CouponRepositoryInterface $subject
     * @param CouponInterface $coupon
     * @return CouponInterface
     */
    public function afterSave(
        CouponRepositoryInterface $subject,
        CouponInterface $coupon
    ) {
        $extensionAttributes = $coupon->getExtensionAttributes();
        if ($extensionAttributes && $ddgExtensionAttributes = $extensionAttributes->getDdgExtensionAttributes()) {
            // Stamp the persisted coupon id onto the attribute. email_coupon_attribute.
            // salesrule_coupon_id is NOT NULL with an FK to salesrule_coupon, but callers are free
            // to build the attribute before the coupon has an id. afterSave always runs after the
            // coupon itself has been written, so the id is guaranteed to be populated here.
            // Value-identical for coupons that already carried an id.
            /** @var CouponAttribute $ddgExtensionAttributes */
            $ddgExtensionAttributes->setCouponId((int) $coupon->getCouponId());
            $this->couponAttributeRepository->save($ddgExtensionAttributes);
        }

        return $coupon;
    }
}
