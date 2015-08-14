<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Column_Renderer_Website extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render grid columns.
     * @param Varien_Object $row
     *
     * @return string
     */
    public function render(Varien_Object $row)
    {
        return Mage::app()->getStore($this->_getValue($row))->getWebsiteId();
    }
}
