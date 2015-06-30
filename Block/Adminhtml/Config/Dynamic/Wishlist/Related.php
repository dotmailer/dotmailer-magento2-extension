<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\Wishlist;

class Related extends \Magento\Config\Block\System\Config\Form\Field
{

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
	    return 'wishlist related';
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
        $text = sprintf('%sconnector/wishlist/related/code/%s/customer_id/@%s@', $baseUrl, $passcode, $customerId);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}