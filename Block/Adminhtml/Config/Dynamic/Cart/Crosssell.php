<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\Cart;

class Crosssell extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
	    return 'crosssell';
        //base url
	    $baseUrl = Mage::helper('ddg')->generateDynamicUrl();
	    //config passcode
        $passcode = Mage::helper('ddg')->getPasscode();
        //last quote id for dynamic page
	    $lastQuoteId = Mage::helper('ddg')->getLastQuoteId();

        if (!strlen($passcode))
	        $passcode = '[PLEASE SET UP A PASSCODE]';
	    //alert message for last order id is not mapped
        if (!$lastQuoteId)
            $lastQuoteId = '[PLEASE MAP THE LAST QUOTE ID]';

	    //full url for dynamic content
        $text =   sprintf('%sconnector/quoteproducts/crosssell/code/%s/quote_id/@%s@', $baseUrl, $passcode,  $lastQuoteId);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}