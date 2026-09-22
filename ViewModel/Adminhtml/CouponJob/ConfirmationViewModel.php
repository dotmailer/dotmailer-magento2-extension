<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\ViewModel\Adminhtml\CouponJob;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\SalesRule\Helper\Coupon;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class ConfirmationViewModel implements ArgumentInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Coupon $couponHelper
     */
    private Coupon $couponHelper;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Coupon $couponHelper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Coupon $couponHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->couponHelper = $couponHelper;
    }

    /**
     * Return the configured coupon job batch size.
     *
     * @return int
     */
    public function getBatchSize(): int
    {
        return (int) $this->scopeConfig->getValue(Config::XML_PATH_COUPON_JOB_BATCH_SIZE);
    }

    /**
     * Return the configured code separator used by coupon generation.
     *
     * @return string
     */
    public function getCodeSeparator(): string
    {
        return $this->couponHelper->getCodeSeparator();
    }
}
