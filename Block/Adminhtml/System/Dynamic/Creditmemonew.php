<?php

class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Creditmemonew extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {

	    //base url
	    $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

	    //config code
        $code = Mage::helper('ddg')->getPasscode();
        $orderId = Mage::helper('ddg')->getMappedOrderId();

	    //message to set up the passcode
        if (!strlen($code))
            $code = '[PLEASE SET UP A PASSCODE]';
	    //full url for dynamic content
        $text = sprintf('%s/connector/creditmemo/new/code/%s/id/@%s@', $baseUrl, $code, $orderId);

        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }

}