<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Recentlyviewed extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
	    return 'product recently viewed';

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