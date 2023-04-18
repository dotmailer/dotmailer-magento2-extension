<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Createdatafield extends Field
{
    /**
     * @var string
     */
    protected $_template = 'Dotdigitalgroup_Email::system/config/createdatafield.phtml';

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
            'dotdigitalgroup_email/datafield/save',
            ['website_id' => $this->getRequest()->getParam('website', 0)]
        );
        $this->addData(
            [
                'button_label' => $buttonLabel,
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $url,
            ]
        );

        return $this->_toHtml();
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
}
