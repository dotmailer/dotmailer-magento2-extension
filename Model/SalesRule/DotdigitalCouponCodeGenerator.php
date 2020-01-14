<?php

namespace Dotdigitalgroup\Email\Model\SalesRule;

use Magento\Framework\Math\Random;
use Magento\SalesRule\Helper\Coupon;
use Magento\SalesRule\Model\Coupon\CodegeneratorInterface;
use Magento\Framework\DataObject;

class DotdigitalCouponCodeGenerator extends DataObject implements CodegeneratorInterface
{
    const SPLIT = 3;
    const LENGTH = 9;

    /**
     * @var Coupon
     */
    private $salesRuleCoupon;

    /**
     * @param Coupon $salesRuleCoupon
     * @param array $data
     */
    public function __construct(
        Coupon $salesRuleCoupon,
        array $data = []
    ) {
        $this->salesRuleCoupon = $salesRuleCoupon;
        parent::__construct($data);
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateCode()
    {
        $prefix = $this->getData('codePrefix') ?: 'DOT-';
        $suffix = $this->getData('codeSuffix') ?: '';
        $format = in_array(
            $codeFormat = $this->getData('codeFormat'),
            array_keys($this->salesRuleCoupon->getFormatsList())
        )
            ? $codeFormat
            : Coupon::COUPON_FORMAT_ALPHANUMERIC;

        $charset = $this->salesRuleCoupon->getCharset($format);
        $charsetSize = count($charset);
        $splitChar = $this->getDelimiter();
        $code = '';

        for ($i = 0; $i < self::LENGTH; ++$i) {
            $char = $charset[Random::getRandomNumber(0, $charsetSize - 1)];
            if ($i % self::SPLIT === 0 && $i !== 0) {
                $char = $splitChar . $char;
            }
            $code .= $char;
        }

        return $prefix . $code . $suffix;
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
