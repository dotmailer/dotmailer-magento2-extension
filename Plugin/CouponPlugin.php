<?php

namespace Dotdigitalgroup\Email\Plugin;

use Dotdigitalgroup\Email\Api\CouponAttributeRepositoryInterface;
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
     * @param CouponRepositoryInterface $subject
     * @param CouponInterface $entity
     * @return CouponInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetById(
        CouponRepositoryInterface $subject,
        CouponInterface $entity
    ) {
        try {
            $couponAttribute = $this->couponAttributeRepository->getById($entity->getId());
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
            $this->couponAttributeRepository->save($ddgExtensionAttributes);
        }

        return $coupon;
    }
}
