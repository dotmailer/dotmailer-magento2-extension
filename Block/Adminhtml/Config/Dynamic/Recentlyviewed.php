<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Recentlyviewed extends \Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\ReadonlyFormField
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $dataHelper;

    /**
     * Recentlyviewed constructor.
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
