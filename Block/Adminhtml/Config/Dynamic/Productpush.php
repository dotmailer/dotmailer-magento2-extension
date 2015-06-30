<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Productpush extends \Magento\Config\Block\System\Config\Form\Field
{
    /** label */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
	    return 'product push';
	    //generate base url
	    $baseUrl = Mage::helper('ddg')->generateDynamicUrl();
        $passcode  = Mage::helper('ddg')->getPasscode();

        if (!strlen($passcode))
	        $passcode = '[PLEASE SET UP A PASSCODE]';

	    //full url for dynamic content
        $text = sprintf('%sconnector/products/push/code/%s', $baseUrl, $passcode);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}