<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Related extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {

	    return 'test';
	    //passcode to append for url
	    $passcode = Mage::helper('ddg')->getPasscode();
	    //last order id witch information will be generated
	    $lastOrderId = Mage::helper('ddg')->getLastOrderId();

	    if(!strlen($passcode))
		    $passcode = '[PLEASE SET UP A PASSCODE]';
	    if(!$lastOrderId)
		    $lastOrderId = '[PLEASE MAP THE LAST ORDER ID]';

	    //generate the base url and display for default store id
	    $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

	    //display the full url
        $text = sprintf('%sconnector/products/related/code/%s/order_id/@%s@', $baseUrl, $passcode, $lastOrderId);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}