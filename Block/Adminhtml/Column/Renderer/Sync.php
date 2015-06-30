<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Column_Renderer_Sync extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
	 * Render the grid columns.
	 *
	 * @param Varien_Object $row
	 * @return string
	 */
    public function render(Varien_Object $row)
    {
        return '<button title="Connect" type="button" style=""><span><span><span>Sync Now</span></span></span></button>';
    }

}