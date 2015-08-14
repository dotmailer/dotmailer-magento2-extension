<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Column_Renderer_Delete extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render the grid columns.
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $url = HtmlSpecialChars(json_encode(Mage::helper('adminhtml')->getUrl('*/*/delete', array('id' => $row->getId()))));
        return '<button title="Delete" onclick="visitPage(' . $url . ')" type="button" style=""><span><span><span>Delete</span></span></span></button>';
    }

}