<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Mostviewed extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_dataHelper;

    /**
     * Mostviewed constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data      $dataHelper
     * @param \Magento\Backend\Block\Template\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $dataHelper,
        \Magento\Backend\Block\Template\Context $context
    ) {
        $this->_dataHelper = $dataHelper;

        parent::__construct($context);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        //base url for dynamic content
        $baseUrl = $this->_dataHelper->generateDynamicUrl();
        $passcode = $this->_dataHelper->getPasscode();

        if (!strlen($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }

        //full url for dynamic content
        $text = sprintf(
            '%sconnector/report/mostviewed/code/%s', $baseUrl, $passcode
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}
