<?php
namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Sync
	extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{


	/**
	 * @param \Magento\Framework\DataObject $row
	 *
	 * @return string
	 */
	public function render(\Magento\Framework\DataObject $row)
	{
		return '<button title="Connect" type="button" style=""><span><span><span>Sync Now</span></span></span></button>';
	}

}