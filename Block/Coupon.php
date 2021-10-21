<?php

namespace Dotdigitalgroup\Email\Block;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\SalesRule\DotdigitalCouponRequestProcessor;
use Dotdigitalgroup\Email\Model\SalesRule\DotdigitalCouponRequestProcessorFactory;
use Dotdigitalgroup\Email\Block\Helper\Font;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var DotdigitalCouponRequestProcessorFactory
     */
    private $dotdigitalCouponRequestProcessorFactory;

    /**
     * @var DotdigitalCouponRequestProcessor
     */
    private $dotdigitalCouponRequestProcessor;

    /**
     * @var Font
     */
    private $font;

    /**
     * @var string
     */
    private $couponCode;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * @param Context $context
     * @param Data $helper
     * @param DotdigitalCouponRequestProcessorFactory $dotdigitalCouponRequestProcessorFactory
     * @param Font $font
     * @param StoreManagerInterface $storeManager
     * @param array $data
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        Context $context,
        Data $helper,
        DotdigitalCouponRequestProcessorFactory $dotdigitalCouponRequestProcessorFactory,
        Font $font,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->dotdigitalCouponRequestProcessorFactory = $dotdigitalCouponRequestProcessorFactory;
        $this->font = $font;
        $this->websiteId = $storeManager->getStore()->getWebsiteId();
        parent::__construct($context, $data);
    }

    /**
     * Generates the coupon code based on the code id.
     *
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateCoupon()
    {
        try {
            // Protects against repeat generation
            if (!empty($this->couponCode)) {
                return $this->couponCode;
            }
            $this->couponCode = $this->getCouponRequestProcessor()
                ->processCouponRequest($this->getRequest()->getParams())
                ->getCouponCode();
            return $this->couponCode;
        } catch (\ErrorException $e) {
            $this->helper->debug('Problem generating coupon', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * @return DotdigitalCouponRequestProcessor
     */
    public function getCouponRequestProcessor()
    {
        return $this->dotdigitalCouponRequestProcessor
            ?: $this->dotdigitalCouponRequestProcessor = $this->dotdigitalCouponRequestProcessorFactory->create();
    }

    /**
     * @return array
     */
    public function getStyle()
    {
        return explode(
            ',',
            $this->helper->getWebsiteConfig(
                Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_STYLE,
                $this->websiteId
            )
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
            Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_COLOR,
            $this->websiteId
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
            Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_FONT_SIZE,
            $this->websiteId
        );
    }

    /**
     * @return bool|string
     */
    public function getHtmlFontFamily()
    {
        return $this->font->getEscapedFontFamilyForCoupon();
    }

    /**
     * Coupon background color from config.
     *
     * @return string|boolean
     */
    public function getBackgroundColor()
    {
        return $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_BG_COLOR,
            $this->websiteId
        );
    }
}
