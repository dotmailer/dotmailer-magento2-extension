<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Recentlyviewed extends \Magento\Config\Block\System\Config\Form\Field
{

    protected $_dataHelper;

    /**
     * Recentlyviewed constructor.
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

    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        //generate base url for dynamic content
        $baseUrl = $this->_dataHelper->generateDynamicUrl();

        //config passcode
        $passcode   = $this->_dataHelper->getPasscode();
        $customerId = $this->_dataHelper->getMappedCustomerId();

        if ( ! strlen($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }
        if ( ! $customerId) {
            $customerId = '[PLEASE MAP THE CUSTOMER ID]';
        }
        //dynamic content url
        $text
            = sprintf('%sconnector/report/recentlyviewed/code/%s/customer_id/@%s@',
            $baseUrl, $passcode, $customerId);
        $element->setData('value', $text);

        return parent::_getElementHtml($element);

    }
}