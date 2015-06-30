<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Sync_Catalog_Values
{
    /**
     * Options getter. Styling options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => '1',
                'label' => 'Default Level'
            ),
            array(
                'value' => '2',
                'label' => 'Store Level'
            )
        );
    }
}