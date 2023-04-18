<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Accounts;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class IntegrationSetupProgress extends Field
{
    /**
     * @var string
     */
    protected $_template = 'integration/setup.phtml';

    /**
     * Get element html.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->toHtml();
    }

    /**
     * Removes use Default Checkbox.
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }
}
