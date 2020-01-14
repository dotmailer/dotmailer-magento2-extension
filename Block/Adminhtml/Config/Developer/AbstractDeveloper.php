<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

abstract class AbstractDeveloper extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @return bool
     */
    abstract protected function getDisabled();

    /**
     * @return string
     */
    abstract protected function getButtonLabel();

    /**
     * @return string
     */
    abstract protected function getButtonUrl();

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->getLayout()
            ->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setType('button')
            ->setLabel($this->getButtonLabel())
            ->setOnClick("window.location.href='" . $this->getButtonUrl() . "'")
            ->setDisabled($this->getDisabled())
            ->toHtml();
    }

    /**
     * Removes use Default Checkbox
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
