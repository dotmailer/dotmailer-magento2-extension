<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Couponcode extends \Magento\Config\Block\System\Config\Form\Field
{
	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $dataHelper,
		\Magento\Backend\Block\Template\Context $context
	)
	{
		$this->_dataHelper = $dataHelper;

		parent::__construct($context);
	}

	/** label */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
	    //base url
        $baseUrl = $this->_dataHelper->generateDynamicUrl();
	    //config code
        $passcode = $this->_dataHelper->getPasscode();

	    if (!strlen($passcode))
            $passcode = '[PLEASE SET UP A PASSCODE]';

	    //full url
	    $text = $baseUrl  . 'connector/email/coupon/id/[INSERT ID HERE]/code/'. $passcode . '/@EMAIL@';
        $element->setData('value', $text);
        $element->setData('disabled', 'disabled');

        return parent::_getElementHtml($element);
    }

}