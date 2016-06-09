<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Imported extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        return '<div ' . (($this->_getValue($row) == '1'
            || $this->_getValue($row) == true)
            ? 'class="dotmailer-success"  '
            :
            'class="dotmailer-error"  ') . '>  </div>';
    }
}
