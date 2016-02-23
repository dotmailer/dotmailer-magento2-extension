<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Status
    extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    /**
     * @param \Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($this->getValue($row) == '1') {
            return 'Subscribed';
        }

        return 'Unsubscribed';
    }

}