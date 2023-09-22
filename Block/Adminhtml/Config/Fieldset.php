<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config;

use Dotdigitalgroup\Email\Block\Adminhtml\Config\Configuration\Preview;

class Fieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        /** @var Preview $block */
        $block = $this->getLayout()->createBlock(
            \Dotdigitalgroup\Email\Block\Adminhtml\Config\Configuration\Preview::class,
            'ddg_automation_dynamic_preview'
        );

        $this->setElement($element);
        $header = $this->_getHeaderHtml($element);

        $elements = '';
        foreach ($element->getElements() as $field) {
            if ($field instanceof \Magento\Framework\Data\Form\Element\Fieldset) {
                $elements .= '<tr id="row_' . $field->getHtmlId() . '">'
                    . '<td colspan="4">' . $field->toHtml() . '</td></tr>';
            } else {
                $elements .= $field->toHtml();
            }
        }

        $footer = $this->_getFooterHtml($element);

        return $header . $block->_toHtml() . $elements . $footer;
    }
}
