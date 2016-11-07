<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

class Fieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @codingStandardsIgnoreStart
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $block = $this->getLayout()->createBlock(
            'Dotdigitalgroup\Email\Block\Adminhtml\Config\Configuration\Preview',
            'ddg_automation_dynamic_preview'
        )->setTemplate(
            'system/preview.phtml'
        );
        $this->setElement($element);
        $header = $this->_getHeaderHtml($element);

        $elements = $this->_getChildrenElementsHtml($element);

        $footer = $this->_getFooterHtml($element);

        return $header . $block->_toHtml() . $elements . $footer;
    }
}
