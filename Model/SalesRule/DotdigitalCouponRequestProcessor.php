<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\SalesRule;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Coupon\CouponAttributeCollection;
use Dotdigitalgroup\Email\Model\Coupon\CouponAttributeCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\SalesRule\Model\ResourceModel\Rule;
use Magento\SalesRule\Model\Rule as RuleModel;
use Magento\SalesRule\Model\RuleFactory;

class DotdigitalCouponRequestProcessor
{
    public const STATUS_GENERATED = 'generated';
    public const STATUS_REGENERATED = 'regenerated';
    public const STATUS_RESENT = 'resent';
    public const STATUS_USED_EXPIRED = 'used_expired';
    public const STATUS_EMAIL_INVALID = 'email_invalid';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var Rule
     */
    private $ruleResource;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var string
     */
    private $couponCode;

    /**
     * @var string
     */
    private $couponGeneratorStatus;

    /**
     * @var CouponAttributeCollectionFactory
     */
    private $couponAttributeCollectionFactory;

    /**
     * @var CouponAttributeCollection
     */
    private $couponAttributeCollection;

    /**
     * @var DotdigitalCouponGenerator
     */
    private $dotdigitalCouponGenerator;

    /**
     * @param Logger $logger
     * @param RuleFactory $ruleFactory
     * @param Rule $ruleResource
     * @param RequestInterface $request
     * @param DateTime $dateTime
     * @param TimezoneInterface $timezoneInterface
     * @param CouponAttributeCollectionFactory $couponAttributeCollectionFactory
     * @param DotdigitalCouponGenerator $dotdigitalCouponGenerator
     */
    public function __construct(
        Logger $logger,
        RuleFactory $ruleFactory,
        Rule $ruleResource,
        RequestInterface $request,
        DateTime $dateTime,
        TimezoneInterface $timezoneInterface,
        CouponAttributeCollectionFactory $couponAttributeCollectionFactory,
        DotdigitalCouponGenerator $dotdigitalCouponGenerator
    ) {
        $this->logger = $logger;
        $this->ruleFactory = $ruleFactory;
        $this->ruleResource = $ruleResource;
        $this->request = $request;
        $this->dateTime = $dateTime;
        $this->localeDate = $timezoneInterface;
        $this->couponAttributeCollectionFactory = $couponAttributeCollectionFactory;
        $this->dotdigitalCouponGenerator = $dotdigitalCouponGenerator;
    }

    /**
     * Process coupon request.
     *
     * @param array $params
     * @return $this
     * @throws \ErrorException
     */
    public function processCouponRequest(array $params)
    {
        if ($this->request->getParam('debug')) {
            $this->logger->debug(
                sprintf("Coupon request for sales rule id %s being processed", $params['id']),
                $params
            );
        }

        if ($this->couponGeneratorStatus !== null) {
            throw new \ErrorException('Already processed');
        }

        $rule = $this->getPriceRule((int) $params['id']);

        // check rule expiry
        if ($this->isRuleExpired($rule)) {
            $this->couponGeneratorStatus = self::STATUS_USED_EXPIRED;
            if ($this->request->getParam('debug')) {
                $this->logger->debug(
                    sprintf("Rule id %s is expired", $params['id'])
                );
            }
            return $this;
        }

        $email = $params['code_email'] ?? null;
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Replace space with + and re-validate
            $email = str_replace(' ', '+', $email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->couponGeneratorStatus = self::STATUS_EMAIL_INVALID;
                return $this;
            }
        }

        $this->couponGeneratorStatus = self::STATUS_GENERATED;

        if ($email) {
            // an existing coupon for the email address exists
            if ($activeCoupon = $this->getActiveCouponForEmail($rule, $email)) {
                if ($this->request->getParam('debug')) {
                    $this->logger->debug(
                        sprintf("Active coupon %s found for %s", $activeCoupon->code, $email)
                    );
                }
                return $this->handleExistingCoupon($activeCoupon, $params, $rule, $email);
            }
        }

        return $this->generateNewCoupon($params, $rule, $email);
    }

    /**
     * Get coupon code.
     *
     * @return string|null
     */
    public function getCouponCode()
    {
        return $this->couponCode;
    }

    /**
     * Get coupon generator status.
     *
     * @return string
     */
    public function getCouponGeneratorStatus()
    {
        return $this->couponGeneratorStatus;
    }

    /**
     * Handle existing coupons.
     *
     * If we find an active coupon for the email address, use the params to determine how to handle this.
     *
     * @param object $activeCoupon
     * @param array $params
     * @param RuleModel $rule
     * @param string $email
     *
     * @return $this
     * @throws \ErrorException
     */
    private function handleExistingCoupon($activeCoupon, array $params, RuleModel $rule, string $email)
    {
        $allowResend = (bool) ($params['code_allow_resend'] ?? 0);
        $cancelSend = (bool) ($params['code_cancel_send'] ?? 0);

        if ($allowResend) {
            if ($cancelSend) {
                if ($activeCoupon->is_expired) {
                    if ($this->request->getParam('debug')) {
                        $this->logger->debug(
                            sprintf("Coupon code %s is expired", $activeCoupon->code)
                        );
                    }
                    return $this->generateNewCoupon($params, $rule, $email);
                } elseif ($activeCoupon->is_used) {
                    $this->couponGeneratorStatus = self::STATUS_USED_EXPIRED;
                    if ($this->request->getParam('debug')) {
                        $this->logger->debug(
                            sprintf("Coupon code %s is used", $activeCoupon->code)
                        );
                    }
                    return $this;
                }
            } else {
                if ($activeCoupon->is_used || $activeCoupon->is_expired) {
                    return $this->generateNewCoupon($params, $rule, $email);
                }
            }
            $this->couponGeneratorStatus = self::STATUS_RESENT;
            $this->couponCode = $activeCoupon->code;
            return $this;
        }
        $this->couponGeneratorStatus = self::STATUS_REGENERATED;
        return $this->generateNewCoupon($params, $rule, $email);
    }

    /**
     * Generate new coupon.
     *
     * @param array $params
     * @param RuleModel $rule
     * @param string|null $email
     * @return $this
     * @throws \ErrorException
     */
    private function generateNewCoupon(array $params, RuleModel $rule, ?string $email)
    {
        if ($this->request->getParam('debug')) {
            $this->logger->debug(
                sprintf("New coupon requested for %s", $email)
            );
        }

        $expireDays = $params['code_expires_after'] ?? null;

        try {
            $this->couponCode = $this->dotdigitalCouponGenerator->generateCoupon(
                $rule,
                $params['code_format'] ?? null,
                $params['code_prefix'] ?? null,
                $params['code_suffix'] ?? null,
                $email,
                $expireDays ? (int) $expireDays : null
            );
        } catch (LocalizedException $e) {
            throw new \ErrorException(sprintf(
                '%s [%s]',
                __('Coupon cannot be created for the rule specified'),
                $e->getMessage()
            ));
        }

        return $this;
    }

    /**
     * Get active coupon for supplied email.
     *
     * @param RuleModel $rule
     * @param string $email
     * @return object|null
     * @throws \Exception
     */
    private function getActiveCouponForEmail(RuleModel $rule, string $email)
    {
        $couponData = $this->getCouponAttributeCollection()
            ->getActiveCouponsForEmail((int) $rule->getRuleId(), $email)
            ->getLastItem()
            ->toArray();

        if (empty($couponData)) {
            return null;
        }

        return (object) [
            'code' => $couponData['code'],
            'is_used' => (int) $couponData['times_used'] > 0,
            'is_expired' => $this->isCouponExpired($couponData)
        ];
    }

    /**
     * Check whether rule has expired.
     *
     * @param RuleModel $rule
     * @return bool
     */
    private function isRuleExpired(RuleModel $rule)
    {
        if (!$rule->getToDate()) {
            return false;
        }

        $configTimeZone = $this->localeDate->getConfigTimezone();
        $couponExpiration = new \DateTime($rule->getToDate(), new \DateTimeZone($configTimeZone));
        $couponExpiration->setTime(23, 59, 59);

        return $couponExpiration && $couponExpiration < $this->localeDate->date();
    }

    /**
     * Load price rule.
     *
     * @param int $priceRuleId
     *
     * @return RuleModel
     */
    private function getPriceRule($priceRuleId)
    {
        $rule = $this->ruleFactory->create();
        $this->ruleResource->load($rule, $priceRuleId);

        return $rule;
    }

    /**
     * Get coupon attribute collection.
     *
     * @return CouponAttributeCollection
     */
    private function getCouponAttributeCollection()
    {
        return $this->couponAttributeCollection
            ?: $this->couponAttributeCollection = $this->couponAttributeCollectionFactory->create();
    }

    /**
     * Is coupon expired.
     *
     * @param array $couponData
     * @return bool
     * @throws \Exception
     */
    private function isCouponExpired($couponData)
    {
        return $couponData['expires_at'] ? new \DateTime($couponData['expires_at']) < $this->localeDate->date() : false;
    }
}
