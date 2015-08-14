<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Automation\Feefo;

class Score extends \Magento\Config\Block\System\Config\Form\Field
{

	public function __construct(
		\Dotdigitalgroup\Email\Helper\Data $data,
		\Magento\Backend\Block\Template\Context $context)
	{
		$this->_helper = $data;
		return parent::__construct($context);
	}

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {

		$passcode = $this->_helper->getPasscode();

        if(!strlen($passcode))
            $passcode = '[PLEASE SET UP A PASSCODE]';

        //generate the base url and display for default store id
        $baseUrl = $this->_helper->generateDynamicUrl();

        //display the full url
        $text = sprintf('%sconnector/feefo/score/code/%s', $baseUrl, $passcode);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}