<?php
class Dotdigitalgroup_Email_Block_Adminhtml_System_Dynamic_Wishlist_Upsell extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * label
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        //passcode to append for url
        $passcode = Mage::helper('ddg')->getPasscode();
        //last order id witch information will be generated
        $customerId = Mage::helper('ddg')->getMappedCustomerId();

        if(!strlen($passcode))
            $passcode = '[PLEASE SET UP A PASSCODE]';
        if(!$customerId)
            $customerId = '[PLEASE MAP THE CUSTOMER ID]';

        //generate the base url and display for default store id
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

        //display the full url
        $text = sprintf('%sconnector/wishlist/upsell/code/%s/customer_id/@%s@', $baseUrl, $passcode, $customerId);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}