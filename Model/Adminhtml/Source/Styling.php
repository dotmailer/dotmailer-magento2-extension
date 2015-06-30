<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Styling
{
    /**
     * Options getter. Styling options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'bold', 'label' => 'Bold'),
            array('value' => 'italic', 'label' => 'Italic'),
            array('value' => 'underline', 'label' => 'Underline')
        );
    }
}