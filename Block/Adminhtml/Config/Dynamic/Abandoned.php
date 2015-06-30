<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Abandoned extends \Magento\Config\Block\System\Config\Form\Field
{

    /** label */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {

	    return 'text to display ';
	    //base url for dynamic content
        $baseUrl = Mage::helper('ddg')->generateDynamicUrl();
        $passcode = Mage::helper('ddg')->getPasscode();

	    //last quote id for dynamic page
	    $lastQuoteId = Mage::helper('ddg')->getLastQuoteId();

	    //config passcode
	    if(!strlen($passcode))
            $passcode = '[PLEASE SET UP A PASSCODE]';
	    //alert message for last order id is not mapped
	    if (!$lastQuoteId)
		    $lastQuoteId = '[PLEASE MAP THE LAST QUOTE ID]';

	    // full url

        $text =  sprintf("%sconnector/email/basket/code/%s/quote_id/@%s@", $baseUrl, $passcode, $lastQuoteId);

        $element->setData('value', $text);
        return parent::_getElementHtml($element);
    }

}