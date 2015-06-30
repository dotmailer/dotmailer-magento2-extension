<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Column_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
	 * Render the grid columns.
	 *
	 * @param Varien_Object $row
	 * @return string
	 */
    public function render(Varien_Object $row)
    {
        if($this->getValue($row) == '1')
            return 'Subscribed';
        return 'Unsubscribed';
    }

}