<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Createdatafield extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Button label.
     *
     * @var string
     */
    public $buttonLabel = 'New Datafield';

    /**
     * @param string $buttonLabel
     *
     * @return $this
     */
    public function setButtonLabel($buttonLabel)
    {
        $this->buttonLabel = $buttonLabel;

        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('Dotdigitalgroup_Email::system/config/createdatafield.phtml');
        }

        return $this;
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
     * @param string $title
     *
     * @return string
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getAddRowButtonHtml($title)
    {
        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setLabel($title)
            ->setOnClick('createDatafield(this.form, this);')
            ->toHtml();
    }
}
