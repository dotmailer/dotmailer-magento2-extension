<?php

namespace Dotdigitalgroup\Email\Model\SalesRule;

use Magento\Framework\Stdlib\DateTime;
use Magento\SalesRule\Model\Coupon\CodegeneratorInterface;
use Magento\SalesRule\Model\ResourceModel\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRule\Model\Spi\CouponResourceInterface;

class DotmailerCouponGenerator
{
    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var Rule
     */
    private $ruleResource;

    /**
     * @var CodegeneratorInterface
     */
    private $couponCodeGenerator;

    /**
     * @var CouponResourceInterface
     */
    private $couponResourceInterface;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param RuleFactory $ruleFactory
     * @param Rule $ruleResource
     * @param CodegeneratorInterface $couponCodeGenerator
     * @param CouponResourceInterface $couponResourceInterface
     * @param DateTime $dateTime
     */
    public function __construct(
        RuleFactory $ruleFactory,
        Rule $ruleResource,
        CodegeneratorInterface $couponCodeGenerator,
        CouponResourceInterface $couponResourceInterface,
        DateTime $dateTime
    ) {
        $this->ruleFactory             = $ruleFactory;
        $this->ruleResource            = $ruleResource;
        $this->couponCodeGenerator     = $couponCodeGenerator;
        $this->couponResourceInterface = $couponResourceInterface;
        $this->dateTime                = $dateTime;
    }

    /**
     * Generate coupon.
     *
     * @param int $priceRuleId
     * @param \DateTime $expireDate
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateCoupon($priceRuleId, $expireDate)
    {
        $rule   = $this->getPriceRule($priceRuleId);
        $coupon = $rule->acquireCoupon();
        $coupon = $this->setUpCoupon($expireDate, $coupon, $rule);
        $this->couponResourceInterface->save($coupon);

        return $coupon->getCode();
    }

    /**
     * @param int $priceRuleId
     *
     * @return \Magento\SalesRule\Model\Rule
     */
    private function getPriceRule($priceRuleId)
    {
        $rule = $this->ruleFactory->create();
        $this->ruleResource->load($rule, $priceRuleId);
        $rule->setCouponCodeGenerator($this->couponCodeGenerator);
        $rule->setCouponType(
            \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO
        );

        return $rule;
    }

    /**
     * @param \DateTime $expireDate
     * @param \Magento\SalesRule\Model\Coupon $coupon
     * @param $rule
     *
     * @return \Magento\SalesRule\Model\Coupon
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function setUpCoupon($expireDate, $coupon, $rule)
    {
        $coupon->setGeneratedByDotmailer(1);
        $coupon->setType(
            \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
        );
        $coupon = $this->setExpiryDate($coupon, $expireDate, $rule);
        $coupon->setCreatedAt($this->dateTime->formatDate(true));

        return $coupon;
    }

    /**
     * @param \Magento\SalesRule\Model\Coupon $couponModel
     * @param \DateTime $expireDate
     * @param \Magento\SalesRule\Model\Rule $rule
     *
     * @return \Magento\SalesRule\Model\Coupon
     */
    private function setExpiryDate($couponModel, $expireDate, $rule)
    {
        $newExpiryDate = $expireDate;
        if ($rule->getToDate() && ! $expireDate) {
            $newExpiryDate = $rule->getToDate();
        }

        $couponModel->setExpirationDate($newExpiryDate);

        return $couponModel;
    }
}
