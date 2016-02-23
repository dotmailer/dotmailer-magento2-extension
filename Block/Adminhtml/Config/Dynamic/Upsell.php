<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Upsell extends \Magento\Config\Block\System\Config\Form\Field
{

    protected $_dataHelper;

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
        $this->_dataHelper = $dataHelper;

        parent::__construct($context);
    }

    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        //passcode to append for url
        $passcode = $this->_dataHelper->getPasscode();
        //last order id witch information will be generated
        $lastOrderid = $this->_dataHelper->getLastOrderId();

        if ( ! strlen($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }
        if ( ! $lastOrderid) {
            $lastOrderid = '[PLEASE MAP THE LAST ORDER ID]';
        }

        //generate the base url and display for default store id
        $baseUrl = $this->_dataHelper->generateDynamicUrl();

        $text = sprintf('%sconnector/product/upsell/code/%s/order_id/@%s@',
            $baseUrl, $passcode, $lastOrderid);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}