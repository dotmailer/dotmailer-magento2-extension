<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Upsell extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
	    return 'test';
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