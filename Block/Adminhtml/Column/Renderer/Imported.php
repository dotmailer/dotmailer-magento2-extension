<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Column_Renderer_Imported extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
	 * Render grid columns.
	 * @param Varien_Object $row
	 *
	 * @return string
	 */
    public function render(Varien_Object $row)
    {
        return '<img style="padding-top:2px" '.(($this->_getValue($row)=='1' || $this->_getValue($row)==true) ? 'src="'.$this->getSkinUrl('images/success_msg_icon.gif').'" alt="YES" ' :   'src="'.
            $this->getSkinUrl('images/error_msg_icon.gif').'" alt="NO" ').'>';
    }
}
