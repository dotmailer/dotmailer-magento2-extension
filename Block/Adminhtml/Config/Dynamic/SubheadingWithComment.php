<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class SubheadingWithComment extends \Magento\Backend\Block\AbstractBlock implements
    \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    /**
     * Render element html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return sprintf(
            '<tr class="system-fieldset-sub-head" id="row_%s">
                        <td colspan="5">
                            <h4 id="%s">%s</h4>
                            <small class="ddg-config-heading-comment">%s</small>
                        </td>
                    </tr>',
            $element->getHtmlId(),
            $element->getHtmlId(),
            $element->getLabel(),
            $element->getComment()
        );
    }
}
