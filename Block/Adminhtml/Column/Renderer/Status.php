<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Status extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
	 * Render the grid columns.
	 *
	 * @return string
	 */
    public function render(\Magento\Framework\DataObject $row)
    {
        if($this->getValue($row) == '1')
            return 'Subscribed';
        return 'Unsubscribed';
    }

}