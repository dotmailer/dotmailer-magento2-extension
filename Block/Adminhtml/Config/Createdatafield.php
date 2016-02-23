<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Createdatafield extends \Magento\Config\Block\System\Config\Form\Field
{

    protected $_buttonLabel = 'New Datafield';

    /**
     * Set Validate VAT Button Label
     *
     * @param string $buttonLabel
     *
     * @return \Magento\Customer\Block\Adminhtml\System\Config\Validatevat
     */
    public function setButtonLabel($buttonLabel)
    {
        $this->_buttonLabel = $buttonLabel;

        return $this;
    }

    /**
     * Set template to itself
     *
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ( ! $this->getTemplate()) {
            $this->setTemplate('system/config/createdatafield.phtml');
        }

        return $this;
    }


    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $originalData = $element->getOriginalData();
        $buttonLabel  = ! empty($originalData['button_label'])
            ? $originalData['button_label'] : $this->_buttonLabel;
        $url
                      = $this->_urlBuilder->getUrl('dotdigitalgroup_email/datafield/save');
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id'      => $element->getHtmlId(),
                'ajax_url'     => $url,
            ]
        );

        return $this->_toHtml();
    }

    /**
     * Unset some non-related element parameters
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

    protected function _getAddRowButtonHtml($title)
    {
        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($this->__($title))
            ->setOnClick("createDatafield(this.form, this);")
            ->toHtml();
    }
}