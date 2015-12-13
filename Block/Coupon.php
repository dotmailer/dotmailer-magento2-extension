<?php

namespace Dotdigitalgroup\Email\Block;

class Coupon extends \Magento\Framework\View\Element\Template
{
	public $helper;
	public $scopeManager;
	public $objectManager;
	protected $_ruleFactory;
	protected $_massgeneratorFactory;
	protected $_couponFactory;

	public function __construct(
		\Magento\Salesrule\Model\Coupon\MassgeneratorFactory $massgeneratorFactory,
		\Magento\Salesrule\Model\RuleFactory $ruleFactory,
		\Magento\Salesrule\Model\CouponFactory $couponFactory,
		\Magento\Framework\View\Element\Template\Context $context,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	)
	{
		parent::__construct( $context, $data );
		$this->helper = $helper;
		$this->_ruleFactory = $ruleFactory;
		$this->_massgeneratorFactory = $massgeneratorFactory;
		$this->_couponFactory = $couponFactory;
		$this->scopeManager = $scopeConfig;
		$this->storeManager = $this->_storeManager;
		$this->objectManager = $objectManagerInterface;
	}
    /**
	 * Generates the coupon code based on the code id.
	 * @return bool
	 */
    public function generateCoupon()
    {
        $params = $this->getRequest()->getParams();
        if (!isset($params['id']) || !isset($params['code'])){
            //throw new Exception('Coupon no id or code is set');
            $this->helper->log('Coupon no id or code is set');
            return false;
        }
        //coupon rule id
        $couponCodeId = $params['id'];

        if ($couponCodeId) {

            $rule = $this->_ruleFactory->create()->load($couponCodeId);
            $generator = $this->_massgeneratorFactory->create();
            $generator->setFormat( \Magento\SalesRule\Helper\Coupon::COUPON_FORMAT_ALPHANUMERIC );
            $generator->setRuleId($couponCodeId);
            $generator->setUsesPerCoupon(1);
            $generator->setDash(3);
            $generator->setLength(9);
            $generator->setPrefix('');
            $generator->setSuffix('');
            //set the generation settings
            $rule->setCouponCodeGenerator($generator);
            $rule->setCouponType(\Magento\SalesRule\Model\Rule::COUPON_TYPE_AUTO);
            //generate the coupon
            $coupon = $rule->acquireCoupon();
            $couponCode = $coupon->getCode();
            //save the type of coupon
            $couponModel = $this->_couponFactory->create()->loadByCode($couponCode);
            $couponModel->setType(\Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON);
            $couponModel->save();

            return $couponCode;
        }
        return false;
    }

}