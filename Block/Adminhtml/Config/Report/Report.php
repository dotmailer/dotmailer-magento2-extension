<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

class Report extends AbstractConfigField
{
    /**
     * Get the button and scripts contents.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->addData(['ajax_url' => $this->getLink()]);

        return $this->_toHtml();
    }
}
