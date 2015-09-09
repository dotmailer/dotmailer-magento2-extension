<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer;

class Imported extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

	/**
	 * @param \Magento\Framework\DataObject $row
	 *
	 * @return string
	 */
	public function rendder(\Magento\Framework\DataObject $row)
	{
		$html = "<script type='application/javascript'>
                    function visitPage(url){
                        document.location.href = url;
                    }
                </script>";
		return $html;
	}
	public function render(\Magento\Framework\DataObject $row)
	{
		return '<img style="padding-top:2px" '.(($this->_getValue($row)=='1' || $this->_getValue($row)==true) ? 'src="'.$this->getSkinUrl('images/success_msg_icon.gif').'" alt="YES" ' :   'src="'.
			$this->getSkinUrl('images/error_msg_icon.gif').'" alt="NO" ').'>';
	}
}
