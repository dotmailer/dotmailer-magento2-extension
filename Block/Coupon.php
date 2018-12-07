<?php

namespace Dotdigitalgroup\Email\Block;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\DateIntervalFactory;
use Dotdigitalgroup\Email\Model\SalesRule\DotmailerCouponGenerator;
use Magento\Framework\View\Element\Template\Context;

/**
 * Coupon block
 *
 * @api
 */
class Coupon extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Data
     */
    public $helper;
    
    /**
     * @var DotmailerCouponGenerator
     */
    private $dotmailerCouponGenerator;

    /**
     * @var DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * Coupon constructor.
     *
     * @param Context $context
     * @param Data $helper
     * @param DotmailerCouponGenerator $dotmailerCouponGenerator
     * @param DateIntervalFactory $dateIntervalFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        DotmailerCouponGenerator $dotmailerCouponGenerator,
        DateIntervalFactory $dateIntervalFactory,
        array $data = []
    ) {
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->helper = $helper;
        $this->dotmailerCouponGenerator = $dotmailerCouponGenerator;
        parent::__construct($context, $data);
    }

    /**
     * Generates the coupon code based on the code id.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateCoupon()
    {
        $params = $this->getRequest()->getParams();
        //check for param code and id
        if (! isset($params['code']) ||
            ! $this->helper->isCodeValid($params['code'])
        ) {
            return false;
        }

        $priceRuleId = (int) $params['id'];
        $expireDate = false;

        if (isset($params['expire_days']) && is_numeric($params['expire_days']) && $params['expire_days'] > 0) {
            $days = (int) $params['expire_days'];
            $expireDate = $this->_localeDate->date()
                ->add($this->dateIntervalFactory->create(['interval_spec' => sprintf('P%sD', $days)]));
        }

        return $this->dotmailerCouponGenerator->generateCoupon($priceRuleId, $expireDate);
    }

    /**
     * @return array
     */
    public function getStyle()
    {
        return explode(
            ',',
            $this->helper->getWebsiteConfig(Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_STYLE)
        );
    }

    /**
     * Coupon color from config.
     *
     * @return mixed
     */
    public function getCouponColor()
    {
        return $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_COLOR
        );
    }

    /**
     * Coupon font size from config.
     *
     * @return int|boolean
     */
    public function getFontSize()
    {
        return $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_FONT_SIZE
        );
    }

    /**
     * Coupon Font from config.
     *
     * @return string|boolean
     */
    public function getFont()
    {
        return $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_FONT
        );
    }

    /**
     * Coupon background color from config.
     *
     * @return string|boolean
     */
    public function getBackgroundColor()
    {
        return $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_BG_COLOR
        );
    }
}
