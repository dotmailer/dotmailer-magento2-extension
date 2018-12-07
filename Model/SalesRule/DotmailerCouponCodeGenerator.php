<?php

namespace Dotdigitalgroup\Email\Model\SalesRule;

use Magento\Framework\Math\Random;
use Magento\SalesRule\Helper\Coupon;
use Magento\SalesRule\Model\Coupon\CodegeneratorInterface;

class DotmailerCouponCodeGenerator implements CodegeneratorInterface
{
    /**
     * @var Coupon
     */
    private $salesRuleCoupon;

    /**
     * @param Coupon $salesRuleCoupon
     */
    public function __construct(
        Coupon $salesRuleCoupon
    ) {
        $this->salesRuleCoupon = $salesRuleCoupon;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateCode()
    {
        $format = Coupon::COUPON_FORMAT_ALPHANUMERIC;
        $splitChar = $this->getDelimiter();
        $charset = $this->salesRuleCoupon->getCharset($format);
        $code = '';
        $charsetSize = count($charset);
        $split = 3;
        $length = 9;
        for ($i = 0; $i < $length; ++$i) {
            $char = $charset[Random::getRandomNumber(0, $charsetSize - 1)];
            if (($split > 0) && (($i % $split) === 0) && ($i !== 0)) {
                $char = $splitChar . $char;
            }
            $code .= $char;
        }

        return 'DOT-' . $code;
    }

    /**
     * Retrieve delimiter
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->salesRuleCoupon->getCodeSeparator();
    }
}
