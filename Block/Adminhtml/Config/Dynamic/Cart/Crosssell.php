<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\Cart;

class Crosssell extends \Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\ReadonlyFormField
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $dataHelper;

    /**
     * Crosssell constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Dotdigitalgroup\Email\Helper\Data $dataHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Dotdigitalgroup\Email\Helper\Data $dataHelper
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
        //base url
        $baseUrl = $this->escapeUrl($this->dataHelper->generateDynamicUrl());
        //config passcode
        $passcode = $this->dataHelper->getPasscode();
        //last quote id for dynamic page
        $lastQuoteId = $this->dataHelper->getLastQuoteId();

        if (empty($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }
        //alert message for last order id is not mapped
        if (!$lastQuoteId) {
            $lastQuoteId = '[PLEASE MAP THE LAST QUOTE ID]';
        }

        //full url for dynamic content
        $text
            = sprintf(
                '%sconnector/quoteproducts/crosssell/code/%s/quote_id/@%s@',
                $baseUrl,
                $passcode,
                $lastQuoteId
            );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}
