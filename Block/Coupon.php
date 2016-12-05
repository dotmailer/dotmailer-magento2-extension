<?php

namespace Dotdigitalgroup\Email\Block;

use Dotdigitalgroup\Email\Helper\Config;

class Coupon extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    public $ruleFactory;
    /**
     * @var \Magento\SalesRule\Model\Coupon\MassgeneratorFactory
     */
    public $massGeneratorFactory;
    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    public $couponFactory;
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Coupon
     */
    public $coupon;

    /**
     * Coupon constructor.
     *
     * @param \Magento\SalesRule\Model\Coupon\MassgeneratorFactory $massgeneratorFactory
     * @param \Magento\SalesRule\Model\CouponFactory               $couponFactory
     * @param \Magento\SalesRule\Model\ResourceModel\Coupon        $coupon
     * @param \Magento\Framework\View\Element\Template\Context     $context
     * @param \Dotdigitalgroup\Email\Helper\Data                   $helper
     * @param \Magento\SalesRule\Model\RuleFactory                 $ruleFactory
     * @param array                                                $data
     */
    public function __construct(
        \Magento\SalesRule\Model\Coupon\MassgeneratorFactory $massgeneratorFactory,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\SalesRule\Model\ResourceModel\Coupon $coupon,
        \Magento\Framework\View\Element\Template\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        array $data = []
    ) {
        $this->helper               = $helper;
        $this->ruleFactory          = $ruleFactory;
        $this->coupon               = $coupon;
        $this->couponFactory        = $couponFactory;
        $this->massGeneratorFactory = $massgeneratorFactory;

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
        if (!isset($params['id']) || !isset($params['code'])) {
            $this->helper->log('Coupon no id or code is set');

            return false;
        }
        //coupon rule id
        $couponCodeId = $params['id'];

        if ($couponCodeId) {
            $rule = $this->ruleFactory->create()
                ->load($couponCodeId);
            $generator = $this->massGeneratorFactory->create();
            $generator->setFormat(
                \Magento\SalesRule\Helper\Coupon::COUPON_FORMAT_ALPHANUMERIC
            );
            $generator->setRuleId($couponCodeId);
            $generator->setUsesPerCoupon(1);
            $generator->setDash(3);
            $generator->setLength(9);
            $generator->setPrefix('DOT-');
            $generator->setSuffix('');
            //set the generation settings
            $rule->setCouponCodeGenerator($generator);
            $rule->setCouponType(
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO
            );
            //generate the coupon
            $coupon = $rule->acquireCoupon();
            $couponCode = $coupon->getCode();
            //save the type of coupon
            /** @var \Magento\SalesRule\Model\Coupon $couponModel */
            $couponModel = $this->couponFactory->create()
                ->loadByCode($couponCode);
            $couponModel->setType(
                \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON
            )->setGeneratedByDotmailer(1);

            if (is_numeric($params['expire_days'])) {
                $expireDate = $this->_localeDate->date()
                    ->add(\DateInterval::createFromDateString(sprintf('P%sD', $params['expire_days'])));

                $couponModel->setExpirationDate($expireDate);
            } elseif ($rule->getToDate()) {
                $couponModel->setExpirationDate($rule->getToDate());
            }

            $this->coupon->save($couponModel);

            return $couponCode;
        }

        return false;
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
     * @return mixed
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
     * @return mixed
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
     * @return mixed
     */
    public function getBackgroundColor()
    {
        return $this->helper->getWebsiteConfig(
            Config::XML_PATH_CONNECTOR_DYNAMIC_COUPON_BG_COLOR
        );
    }
}
