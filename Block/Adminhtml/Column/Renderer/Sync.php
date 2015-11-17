<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Sync extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{


    /**
	 * Render the grid columns.
	 *
	 * @return string
	 */
    public function render(\Magento\Framework\DataObject $row)
    {
        return '<button title="Connect" type="button" style=""><span><span><span>Sync Now</span></span></span></button>';
    }

}