<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Column_Renderer_Reset extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render the grid columns.
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $url = HtmlSpecialChars(json_encode(Mage::helper('adminhtml')->getUrl('*/*/reset', array('id' => $row->getId()))));
        return '<button title="Reset" onclick="visitPage(' . $url . '); return false" type="button" style=""><span><span><span>Reset</span></span></span></button>';
    }

}