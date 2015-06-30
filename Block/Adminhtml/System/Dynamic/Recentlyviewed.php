<?php
class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Recentlyviewed extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //generate base url for dynamic content
	    $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

	    //config passcode
        $passcode = Mage::helper('ddg')->getPasscode();
        $customerId = Mage::helper('ddg')->getMappedCustomerId();

        if (!strlen($passcode))
	        $passcode = '[PLEASE SET UP A PASSCODE]';
        if (!$customerId)
	        $customerId = '[PLEASE MAP THE CUSTOMER ID]';
	    //dynamic content url
        $text = sprintf('%sconnector/report/recentlyviewed/code/%s/customer_id/@%s@', $baseUrl, $passcode, $customerId);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);

    }
}