<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Report;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

abstract class AbstractConfigField extends Field
{
    /**
     * @var string
     */
    protected $linkUrlPath;

    /**
     * @var string
     */
    protected $_template = 'Dotdigitalgroup_Email::system/config/reportlink.phtml';

    /**
     * Get button link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->getUrl($this->linkUrlPath);
    }

    /**
     * Removes use Default Checkbox.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Get the button and scripts contents.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        $this->addData(['html_id' => $element->getHtmlId()]);
        return $this->_toHtml();
    }
}
