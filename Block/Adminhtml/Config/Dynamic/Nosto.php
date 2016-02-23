<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Nosto extends \Magento\Config\Block\System\Config\Form\Field
{

    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $dataHelper,
        \Magento\Backend\Block\Template\Context $context
    ) {
        $this->_dataHelper = $dataHelper;

        parent::__construct($context);
    }

    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        //passcode to append for url
        $passcode = $this->_dataHelper->getPasscode();

        if ( ! strlen($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }

        //generate the base url and display for default store id
        $baseUrl = $this->_dataHelper->generateDynamicUrl();

        //display the full url
        $text
            = sprintf('%sconnector/products/nosto/code/%s/slot/@SLOT_NAME@/email/@EMAIL@',
            $baseUrl, $passcode);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}