<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Upsell extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $dataHelper;

    /**
     * Upsell constructor.
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
        //passcode to append for url
        $passcode = $this->dataHelper->getPasscode();
        //last order id witch information will be generated
        $lastOrderid = $this->dataHelper->getLastOrderId();

        if (empty($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }
        if (!$lastOrderid) {
            $lastOrderid = '[PLEASE MAP THE LAST ORDER ID]';
        }

        //generate the base url and display for default store id
        $baseUrl = $this->dataHelper->generateDynamicUrl();

        $text = sprintf(
            '%sconnector/product/upsell/code/%s/order_id/@%s@',
            $baseUrl,
            $passcode,
            $lastOrderid
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}
