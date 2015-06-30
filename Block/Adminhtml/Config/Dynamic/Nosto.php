<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Nosto extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
	    return 'nosto url';
	    //passcode to append for url
	    $passcode = Mage::helper('ddg')->getPasscode();

	    if(!strlen($passcode))
		    $passcode = '[PLEASE SET UP A PASSCODE]';

	    //generate the base url and display for default store id
	    $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

	    //display the full url
        $text = sprintf('%sconnector/products/nosto/code/%s/slot/@SLOT_NAME@/email/@EMAIL@', $baseUrl, $passcode);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}