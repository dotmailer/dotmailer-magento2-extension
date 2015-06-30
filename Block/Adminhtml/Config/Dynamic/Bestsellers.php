<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Bestsellers extends \Magento\Config\Block\System\Config\Form\Field
{
    /** label */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
	    return 'bestsellers';

	    //base url
	    $baseUrl = Mage::helper('ddg')->generateDynamicUrl();

	    //config passcode
        $passcode = Mage::helper('ddg')->getPasscode();

        if (!strlen($passcode))
	        $passcode = '[PLEASE SET UP A PASSCODE]';

	    //full url
        $text = sprintf('%sconnector/report/bestsellers/code/%s', $baseUrl, $passcode);
        $element->setData('value', $text);
        $element->setData('disabled', 'disabled');

        return parent::_getElementHtml($element);
    }
}