<?php

namespace Dotdigitalgroup\Email\Block;

use Dotdigitalgroup\Email\Helper\Config;

/**
 * Coupon block
 *
 * @api
 */
class Coupon extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Campaign
     */
    private $campaign;

    /**
     * @var \Dotdigitalgroup\Email\Model\DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * Coupon constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context              $context
     * @param \Dotdigitalgroup\Email\Helper\Data                            $helper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign           $campaign
     * @param \Dotdigitalgroup\Email\Model\DateIntervalFactory              $dateIntervalFactory
     * @param array                                                         $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign $campaign,
        \Dotdigitalgroup\Email\Model\DateIntervalFactory $dateIntervalFactory,
        array $data = []
    ) {
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->helper = $helper;
        $this->campaign = $campaign;
        parent::__construct($context, $data);
    }

    /**
     * Generates the coupon code based on the code id.
     *
     * @return bool
     */
    public function generateCoupon()
    {
        $params = $this->getRequest()->getParams();
        //check for param code and id
        if (! isset($params['code']) ||
            ! $this->helper->isCodeValid($params['code'])
        ) {
            $this->helper->log('Coupon no id or valid code is set');

            return false;
        }

        $couponCodeId = (int) $params['id'];
        $expireDate = false;

        if (isset($params['expire_days']) && is_numeric($params['expire_days']) && $params['expire_days'] > 0) {
            $days = (int) $params['expire_days'];
            $expireDate = $this->_localeDate->date()
                ->add($this->dateIntervalFactory->create(['interval_spec' => sprintf('P%sD', $days)]));
        }

        return $this->campaign->generateCoupon($couponCodeId, $expireDate);
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
