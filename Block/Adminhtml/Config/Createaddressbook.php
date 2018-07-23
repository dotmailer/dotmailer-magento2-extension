<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Createaddressbook extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Button label.
     *
     * @var string
     */
    public $vatButtonLabel = 'New Addressbook';

    /**
     * @param string $vatButtonLabel
     *
     * @return $this
     */
    public function setVatButtonLabel($vatButtonLabel)
    {
        $this->vatButtonLabel = $vatButtonLabel;

        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/createaddressbook.phtml');
        }

        return $this;
    }

    /**
     * Unset some non-related element parameters.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Get the button and scripts contents.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $originalData = $element->getOriginalData();
        $buttonLabel = !empty($originalData['button_label'])
            ? $originalData['button_label'] : $this->vatButtonLabel;
        $url = $this->_urlBuilder->getUrl(
            'dotdigitalgroup_email/addressbook/save'
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
}
