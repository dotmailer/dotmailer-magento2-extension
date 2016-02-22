<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Delete
	extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

	/**
	 * @param \Magento\Framework\DataObject $row
	 *
	 * @return string
	 */
	public function render(\Magento\Framework\DataObject $row)
	{
		$url = HtmlSpecialChars(
			json_encode(
				$this->getUrl('*/*/delete', array('id' => $row->getId()))
			)
		);

		return '<button title="Delete" onclick="visitPage(' . $url
		. ')" type="button" style=""><span><span><span>Delete</span></span></span></button>';
	}

}