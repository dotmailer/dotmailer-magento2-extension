<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\SalesRule;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Coupon\CouponAttribute;
use Dotdigitalgroup\Email\Model\Coupon\CouponAttributeFactory;
use Dotdigitalgroup\Email\Model\DateTimeFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Api\Data\CouponExtensionFactory;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Model\Coupon\CodegeneratorInterface;
use Magento\SalesRule\Model\Rule as RuleModel;

class DotdigitalCouponGenerator
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var DotdigitalCouponCodeGenerator
     */
    private $couponCodeGenerator;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

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
     * @var RequestInterface
     */
    private $request;

    /**
     * @var bool|null
     */
    private $isDebug;

    /**
     * @param Logger $logger
     * @param CodegeneratorInterface $couponCodeGenerator
     * @param DateTime $dateTime
     * @param DateTimeFactory $dateTimeFactory
     * @param CouponAttributeFactory $couponAttributeFactory
     * @param CouponExtensionFactory $couponExtensionFactory
     * @param CouponRepositoryInterface $couponRepository
     * @param RequestInterface $request
     */
    public function __construct(
        Logger $logger,
        CodegeneratorInterface $couponCodeGenerator,
        DateTime $dateTime,
        DateTimeFactory $dateTimeFactory,
        CouponAttributeFactory $couponAttributeFactory,
        CouponExtensionFactory $couponExtensionFactory,
        CouponRepositoryInterface $couponRepository,
        RequestInterface $request
    ) {
        $this->logger = $logger;
        $this->couponCodeGenerator = $couponCodeGenerator;
        $this->dateTime = $dateTime;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->couponAttributeFactory = $couponAttributeFactory;
        $this->couponExtensionFactory = $couponExtensionFactory;
        $this->couponRepository = $couponRepository;
        $this->request = $request;
    }

    /**
     * Generate coupon.
     *
     * @param RuleModel $rule
     * @param string|null $codeFormat
     * @param string|null $codePrefix
     * @param string|null $codeSuffix
     * @param string|null $emailAddress
     * @param int|null $expireDays
     * @param int|null $codeLength
     * @param int|null $codeDash
     * @param string|null $expiresAt
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateCoupon(
        RuleModel $rule,
        ?string $codeFormat = null,
        ?string $codePrefix = null,
        ?string $codeSuffix = null,
        ?string $emailAddress = null,
        ?int $expireDays = null,
        ?int $codeLength = null,
        ?int $codeDash = null,
        ?string $expiresAt = null
    ) {
        if ($this->isDebug()) {
            $this->logger->debug(
                "Begin coupon generation",
                [
                    $rule->getId(),
                    $codeFormat,
                    $codePrefix,
                    $codeSuffix,
                    $emailAddress,
                    $expireDays,
                    $codeLength,
                    $codeDash
                ]
            );
        }

        // set format/prefix/suffix to code generator
        $this->couponCodeGenerator->setData([
            'codeFormat' => $codeFormat,
            'codePrefix' => $codePrefix,
            'codeSuffix' => $codeSuffix,
            'codeLength' => $codeLength,
            'codeDash' => $codeDash,
        ]);

        // update rule
        $rule->setCouponCodeGenerator($this->couponCodeGenerator);
        $rule->setCouponType(RuleModel::COUPON_TYPE_AUTO);

        $coupon = $rule->acquireCoupon()
            ->setType(CouponInterface::TYPE_GENERATED)
            ->setCreatedAt($this->dateTime->formatDate(true))
            ->setGeneratedByDotmailer(1);

        /** @var CouponAttribute $dotCouponAttribute */
        $dotCouponAttribute = $this->couponAttributeFactory->create()
            ->setCouponId($coupon->getId());
        $couponExtension = $this->couponExtensionFactory->create();
        $extensionAttributesUpdated = false;

        if ($emailAddress) {
            $dotCouponAttribute->setEmail($emailAddress);
            $couponExtension->setDdgExtensionAttributes($dotCouponAttribute);
            $extensionAttributesUpdated = true;
        }
        $couponExpiresAt = $this->resolveExpiresAt($expireDays, $expiresAt);
        if ($couponExpiresAt) {
            $dotCouponAttribute->setExpiresAt($couponExpiresAt);
            $extensionAttributesUpdated = true;
        }

        if ($extensionAttributesUpdated) {
            $coupon->setExtensionAttributes($couponExtension);
        }

        if ($this->isDebug()) {
            $this->logger->debug(
                "Coupon created, saving ..."
            );
        }

        $this->couponRepository->save($coupon);

        if ($this->isDebug()) {
            $this->logger->debug(
                "Coupon data",
                [$coupon->toArray()]
            );
        }

        return $coupon->getCode();
    }

    /**
     * Whether the request is in debug mode.
     *
     * Memoised - the value cannot change during a request.
     *
     * @return bool
     */
    private function isDebug(): bool
    {
        if ($this->isDebug === null) {
            $this->isDebug = (bool) $this->request->getParam('debug');
        }

        return $this->isDebug;
    }

    /**
     * Resolve the coupon expiry timestamp.
     *
     * @param int|null $expireDays
     * @param string|null $expiresAt
     * @return string|null
     * @throws LocalizedException
     */
    private function resolveExpiresAt(?int $expireDays, ?string $expiresAt): ?string
    {
        $utcTimezone = new \DateTimeZone('UTC');

        if ($expiresAt !== null && $expiresAt !== '') {
            $expiresAtDate = $this->dateTimeFactory->create();
            $expiresAtDate->setDate(
                (int)substr($expiresAt, 0, 4),
                (int)substr($expiresAt, 5, 2),
                (int)substr($expiresAt, 8, 2)
            );
            $expiresAtDate->setTimezone($utcTimezone);
            $expiresAtDate->setTime(23, 59, 59);
            return $expiresAtDate->format('Y-m-d H:i:s');
        }

        if ($expireDays && $expireDays > 0) {
            $expiresAtDate = $this->dateTimeFactory->create()->getUtcDate();
            $expiresAtDate->modify(sprintf('+%s day', $expireDays));
            $expiresAtDate->setTimezone($utcTimezone);
            return $expiresAtDate->format('Y-m-d H:i:s');
        }

        return null;
    }
}
