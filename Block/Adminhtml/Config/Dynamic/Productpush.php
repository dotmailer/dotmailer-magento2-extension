<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class Productpush extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $dataHelper;

    /**
     * Productpush constructor.
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
        //generate base url
        $baseUrl = $this->dataHelper->generateDynamicUrl();
        $passcode = $this->dataHelper->getPasscode();

        if (empty($passcode)) {
            $passcode = '[PLEASE SET UP A PASSCODE]';
        }

        //full url for dynamic content
        $text = sprintf(
            '%sconnector/product/push/code/%s',
            $baseUrl,
            $passcode
        );
        $element->setData('value', $text);

        return parent::_getElementHtml($element);
    }
}
