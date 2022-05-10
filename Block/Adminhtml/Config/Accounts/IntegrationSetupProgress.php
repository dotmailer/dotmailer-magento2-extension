<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Accounts;

class IntegrationSetupProgress extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Prepare layout.
     *
     * @return $this|IntegrationSetupProgress
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate('integration/setup.phtml');
        return $this;
    }

    /**
     * Get element html.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->toHtml();
    }

    /**
     * Removes use Default Checkbox.
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
