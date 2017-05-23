<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

/**
 * Class Catalog
 * @package Dotdigitalgroup\Email\Block\Adminhtml\Config\Report
 */
class Catalog extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @var string
     */
    public $buttonLabel = 'Contact Report';

    /**
     * @param $buttonLabel
     *
     * @return $this
     */
    public function setButtonLabel($buttonLabel)
    {
        $this->buttonLabel = $buttonLabel;

        return $this;
    }

    /**
     * Set template to itself.
     */
    public function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/reportlink.phtml');
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->getUrl(
            'dotdigitalgroup_email/catalog/index'
        );
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
            ? $originalData['button_label'] : $this->buttonLabel;
        $url
                      = $this->_urlBuilder->getUrl('dotdigitalgroup_email/addressbook/save');
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $url,
            ]
        );

        return $this->_toHtml();
    }
}
