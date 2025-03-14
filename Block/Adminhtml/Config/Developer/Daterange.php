<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Config\Developer;

class Daterange extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Get element HTML.
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $ranges = ['from', 'to'];
        $dateElements = '';
        foreach ($ranges as $range) {
            $dateElements .=
                "<div class = 'ddg-config-daterange-wrapper'>" .
                "<p>" . ucfirst($range) . ":</p>
                    <input id='" . $range . "' name='" . $range . "'data-ui-id=''
                        value='' class='ddg-datepicker input-text admin__control-text' type='text' />
                </div>";
        }
        return $dateElements;
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
