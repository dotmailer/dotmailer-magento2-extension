<?php

namespace Dotdigitalgroup\Email\Block;

class Coupon extends \Magento\Framework\View\Element\Template
{
	protected $_quote;
	public $helper;
	public $priceHelper;
	public $scopeManager;
	public $objectManager;


	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Dotdigitalgroup\Email\Helper\Data $helper,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\Pricing\Helper\Data $priceHelper,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	)
	{
		parent::__construct( $context, $data );
		$this->helper = $helper;
		$this->priceHelper = $priceHelper;
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

            $rule = $this->objectManager->create('Magento\Salesrule\Model\Rule')->load($couponCodeId);
            $generator = $this->objectManager->create('Magento\Salesrule\Model\Coupon\Massgenerator');
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
            $couponModel = $this->objectManager->create('Magento\Salesrule\Model\Coupon')->loadByCode($couponCode);
            $couponModel->setType(\Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON);
            $couponModel->save();

            return $couponCode;
        }
        return false;
    }

}