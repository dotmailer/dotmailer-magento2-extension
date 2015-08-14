<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Column_Renderer_Script extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Render the grid columns.
     *
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $html = "<script type='application/javascript'>
                    function visitPage(url){
                        document.location.href = url;
                    }
                </script>";
        return $html;
    }

}