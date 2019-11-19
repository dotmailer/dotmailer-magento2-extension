<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Couponcode extends \Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic\ReadonlyFormField
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $dataHelper;

    /**
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
        $baseUrl = $this->dataHelper->generateDynamicUrl();
        //config code
        $passcode = $this->dataHelper->getPasscode();

        if (empty($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }

        //full url
        $text = $baseUrl . 'connector/email/coupon/id/[INSERT CART PRICE RULE ID HERE]/code/'
            . $passcode . '/@EMAIL@';
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}
