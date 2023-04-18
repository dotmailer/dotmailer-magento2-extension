<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Createaddressbook extends Field
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var string
     */
    protected $_template = 'Dotdigitalgroup_Email::system/config/createaddressbook.phtml';

    /**
     * Createaddressbook constructor.
     * @param Context $context
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Unset some non-related element parameters.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(
        AbstractElement $element
    ) {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Get the button and scripts contents.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(
        AbstractElement $element
    ) {
        $originalData = $element->getOriginalData();
        $buttonLabel = $originalData['button_label'];
        $url = $this->_urlBuilder->getUrl(
            'dotdigitalgroup_email/addressbook/save',
            ['website' => $this->helper->getWebsiteForSelectedScopeInAdmin()->getId()]
        );

        $this->addData(
            [
                'button_label' => $buttonLabel,
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $url
            ]
        );

        return $this->_toHtml();
    }
}
