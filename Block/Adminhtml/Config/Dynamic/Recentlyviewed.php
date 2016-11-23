<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Recentlyviewed extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $dataHelper;

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
        //generate base url for dynamic content
        $baseUrl = $this->dataHelper->generateDynamicUrl();

        //config passcode
        $passcode = $this->dataHelper->getPasscode();
        $customerId = $this->dataHelper->getMappedCustomerId();

        if (empty($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }
        if (!$customerId) {
            $customerId = '[PLEASE MAP THE CUSTOMER ID]';
        }
        //dynamic content url
        $text
            = sprintf(
                '%sconnector/report/recentlyviewed/code/%s/customer_id/@%s@',
                $baseUrl,
                $passcode,
                $customerId
            );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}
