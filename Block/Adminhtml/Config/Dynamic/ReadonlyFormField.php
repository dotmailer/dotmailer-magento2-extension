<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Dynamic;

class ReadonlyFormField extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(
        \Magento\Framework\Data\Form\Element\AbstractElement $element
    ) {
        $element->setData('readonly', 1);
        $element->setData('class', 'ddg-dynamic-content');
        return parent::_getElementHtml($element);
    }
}
