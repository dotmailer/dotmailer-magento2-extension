<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Automation\Feefo;

class Reviews extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;

    /**
     * Reviews constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data      $data
     * @param \Magento\Backend\Block\Template\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $data,
        \Magento\Backend\Block\Template\Context $context
    ) {
        $this->helper = $data;

        parent::__construct($context);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $passcode = $this->helper->getPasscode();

        if (empty($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }

        //generate the base url and display for default store id
        $baseUrl = $this->helper->generateDynamicUrl();

        //display the full url
        $text = sprintf(
            '%sconnector/feefo/reviews/code/%s/quote_id/@QUOTE_ID@',
            $baseUrl,
            $passcode
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}
