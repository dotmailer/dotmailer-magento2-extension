<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Productpush extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /** label */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
	    //generate base url
	    $baseUrl = Mage::helper('ddg')->generateDynamicUrl();
        $passcode  = Mage::helper('ddg')->getPasscode();

        if (!strlen($passcode))
	        $passcode = '[PLEASE SET UP A PASSCODE]';

	    //full url for dynamic content
        $text = sprintf('%sconnector/products/push/code/%s', $baseUrl, $passcode);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}