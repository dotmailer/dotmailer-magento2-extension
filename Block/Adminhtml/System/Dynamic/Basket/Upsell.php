<?php
class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Basket_Upsell extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //passcode to append for url
        $passcode = Mage::helper('ddg')->getPasscode();
        //last quote id for dynamic page
        $lastQuoteId = Mage::helper('ddg')->getLastQuoteId();

        if (!strlen($passcode))
            $passcode = '[PLEASE SET UP A PASSCODE]';
        //alert message for last order id is not mapped
        if (!$lastQuoteId)
            $lastQuoteId = '[PLEASE MAP THE LAST QUOTE ID]';

	    //generate the base url and display for default store id
	    $baseUrl = Mage::helper('ddg')->generateDynamicUrl();
	    
        $text = sprintf('%sconnector/quoteproducts/upsell/code/%s/quote_id/@%s@', $baseUrl, $passcode, $lastQuoteId);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}