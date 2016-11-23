<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Crosssell extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $dataHelper;

    /**
     * Crosssell constructor.
     *
     * @param \Dotdigitalgroup\Email\Helper\Data $dataHelper
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
        //base url
        $baseUrl = $this->dataHelper->generateDynamicUrl();
        //config passcode
        $passcode = $this->dataHelper->getPasscode();
        //last order id for dynamic page
        $lastOrderId = $this->dataHelper->getLastOrderId();

        if (empty($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }
        //alert message for last order id is not mapped
        if (!$lastOrderId) {
            $lastOrderId = '[PLEASE MAP THE LAST ORDER ID]';
        }

        //full url for dynamic content
        $text = sprintf(
            '%sconnector/product/crosssell/code/%s/order_id/@%s@',
            $baseUrl,
            $passcode,
            $lastOrderId
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}
