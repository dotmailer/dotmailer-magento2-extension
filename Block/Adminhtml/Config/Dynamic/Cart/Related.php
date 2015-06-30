<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\Cart;
class Related extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
	    return 'cart related';

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

	    //display the full url
        $text = sprintf('%sconnector/quoteproducts/related/code/%s/quote_id/@%s@', $baseUrl, $passcode, $lastQuoteId);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}