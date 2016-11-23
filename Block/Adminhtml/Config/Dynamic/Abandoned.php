<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Abandoned extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $dataHelper;

    /**
     * Abandoned constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data      $dataHelper
     * @param \Magento\Backend\Block\Template\Context $context
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $dataHelper,
        \Magento\Backend\Block\Template\Context $context
    ) {
        $this->dataHelper = $dataHelper;

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
        //base url for dynamic content
        $baseUrl = $this->dataHelper->generateDynamicUrl();
        $passcode = $this->dataHelper->getPasscode();

        //last quote id for dynamic page
        $lastQuoteId = $this->dataHelper->getLastQuoteId();

        //config passcode
        if (empty($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }
        //alert message for last order id is not mapped
        if (! $lastQuoteId) {
            $lastQuoteId = '[PLEASE MAP THE LAST QUOTE ID]';
        }

        // full url
        $text = sprintf(
            '%sconnector/email/basket/code/%s/quote_id/@%s@',
            $baseUrl,
            $passcode,
            $lastQuoteId
        );

        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}
