<?php
class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Upsell extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //passcode to append for url
        $passcode = Mage::helper('ddg')->getPasscode();
	    //last order id witch information will be generated
        $lastOrderid = Mage::helper('ddg')->getLastOrderId();

        if(!strlen($passcode))
	        $passcode = '[PLEASE SET UP A PASSCODE]';
        if(!$lastOrderid)
	        $lastOrderid = '[PLEASE MAP THE LAST ORDER ID]';

	    //generate the base url and display for default store id
	    $baseUrl = Mage::helper('ddg')->generateDynamicUrl();
	    
        $text = sprintf('%sconnector/products/upsell/code/%s/order_id/@%s@', $baseUrl, $passcode, $lastOrderid);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}