<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Couponcode extends \Magento\Config\Block\System\Config\Form\Field
{
    /** label */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {

	    return 'test';
	    //base url
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();


	    //config code
        $code = Mage::helper('ddg')->getPasscode();

	    if (!strlen($code))
            $code = '[PLEASE SET UP A PASSCODE]';

	    //full url
	    $text = $baseUrl  . 'connector/email/coupon/id/[INSERT ID HERE]/code/'. $code . '/@EMAIL@';

        $element->setData('value', $text);
        $element->setData('disabled', 'disabled');
        return parent::_getElementHtml($element);
    }

}