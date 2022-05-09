<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

abstract class AbstractButton extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Get disabled.
     *
     * @return bool
     */
    abstract protected function getDisabled();

    /**
     * Get button label.
     *
     * @return string
     */
    abstract protected function getButtonLabel();

    /**
     * Get button url.
     *
     * @return string
     */
    abstract protected function getButtonUrl();

    /**
     * Get element html.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $block = $this->getLayout()
            ->createBlock(\Magento\Backend\Block\Widget\Button::class);
        /** @var \Magento\Backend\Block\Widget\Button $block */
        return $block->setType('button')
            ->setLabel($this->getButtonLabel())
            ->setOnClick("window.location.href='" . $this->getButtonUrl() . "'")
            ->setDisabled($this->getDisabled())
            ->toHtml();
    }

    /**
     * Removes use Default Checkbox
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
