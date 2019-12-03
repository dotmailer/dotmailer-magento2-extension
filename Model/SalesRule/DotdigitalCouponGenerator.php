<?php

namespace Dotdigitalgroup\Email\Model\SalesRule;

use Dotdigitalgroup\Email\Model\Coupon\CouponAttribute;
use Magento\Framework\Stdlib\DateTime;
use Magento\SalesRule\Model\Coupon\CodegeneratorInterface;
use Magento\SalesRule\Model\Rule as RuleModel;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Dotdigitalgroup\Email\Model\Coupon\CouponAttributeFactory;
use Magento\SalesRule\Api\Data\CouponExtensionFactory;
use Magento\SalesRule\Api\CouponRepositoryInterface;

class DotdigitalCouponGenerator
{
    /**
     * @var DotdigitalCouponCodeGenerator
     */
    private $couponCodeGenerator;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var CouponExtensionFactory
     */
    private $couponExtensionFactory;

    /**
     * @var CouponRepositoryInterface
     */
    private $couponRepository;

    /**
     * @var CouponAttributeFactory
     */
    private $couponAttributeFactory;

    /**
     * @param CodegeneratorInterface $couponCodeGenerator
     * @param DateTime $dateTime
     * @param TimezoneInterface $timezoneInterface
     * @param CouponAttributeFactory $couponAttributeFactory
     * @param CouponExtensionFactory $couponExtensionFactory
     * @param CouponRepositoryInterface $couponRepository
     */
    public function __construct(
        CodegeneratorInterface $couponCodeGenerator,
        DateTime $dateTime,
        TimezoneInterface $timezoneInterface,
        CouponAttributeFactory $couponAttributeFactory,
        CouponExtensionFactory $couponExtensionFactory,
        CouponRepositoryInterface $couponRepository
    ) {
        $this->couponCodeGenerator = $couponCodeGenerator;
        $this->dateTime = $dateTime;
        $this->localeDate = $timezoneInterface;
        $this->couponAttributeFactory = $couponAttributeFactory;
        $this->couponExtensionFactory = $couponExtensionFactory;
        $this->couponRepository = $couponRepository;
    }

    /**
     * Generate coupon.
     *
     * @param RuleModel $rule
     * @param string|null $codeFormat
     * @param string|null $codePrefix
     * @param string|null $codeSuffix
     * @param string|null $emailAddress
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateCoupon(
        RuleModel $rule,
        string $codeFormat = null,
        string $codePrefix = null,
        string $codeSuffix = null,
        string $emailAddress = null
    ) {
        // set format/prefix/suffix to code generator
        $this->couponCodeGenerator->setData([
            'codeFormat' => $codeFormat,
            'codePrefix' => $codePrefix,
            'codeSuffix' => $codeSuffix,
        ]);

        // update rule
        $rule->setCouponCodeGenerator($this->couponCodeGenerator);
        $rule->setCouponType(RuleModel::COUPON_TYPE_AUTO);

        // get coupon
        $coupon = $rule->acquireCoupon()
            ->setType(RuleModel::COUPON_TYPE_NO_COUPON)
            ->setCreatedAt($this->dateTime->formatDate(true))
            ->setGeneratedByDotmailer(1);

        if ($emailAddress) {
            /** @var CouponAttribute $dotCouponAttribute */
            $dotCouponAttribute = $this->couponAttributeFactory->create()
                ->setCouponId($coupon->getId())
                ->setEmail($emailAddress);

            $couponExtension = $this->couponExtensionFactory->create()
                ->setGeneratedForEmail($dotCouponAttribute);

            $coupon->setExtensionAttributes($couponExtension);
        }

        $this->couponRepository->save($coupon);

        return $coupon->getCode();
    }
}
