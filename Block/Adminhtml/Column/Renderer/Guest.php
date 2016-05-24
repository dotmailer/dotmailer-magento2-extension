<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Guest
    extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        return ($this->_getValue($row) == 1) ? 1 : '';
    }
}
