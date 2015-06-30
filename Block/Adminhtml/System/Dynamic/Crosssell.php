<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Crosssell extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //base url
	    $baseUrl = Mage::helper('ddg')->generateDynamicUrl();
	    //config passcode
        $passcode = Mage::helper('ddg')->getPasscode();
        //last order id for dynamic page
	    $lastOrderId = Mage::helper('ddg')->getLastOrderId();

        if (!strlen($passcode))
	        $passcode = '[PLEASE SET UP A PASSCODE]';
	    //alert message for last order id is not mapped
        if (!$lastOrderId)
	        $lastOrderId = '[PLEASE MAP THE LAST ORDER ID]';

	    //full url for dynamic content
        $text =   sprintf('%sconnector/products/crosssell/code/%s/order_id/@%s@', $baseUrl, $passcode,  $lastOrderId);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}