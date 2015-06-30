<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Datamapping_Datafieldtype
{
    /**
     * Datafield model type.
     * Data mapping.
     * @return array
     */
    public function toOptionArray()
    {
        $dataType = array(
            array('value' => 'String',  'label' => Mage::helper('ddg')->__('String')),
            array('value' => 'Numeric', 'label' => Mage::helper('ddg')->__('Numeric')),
            array('value' => 'Date',    'label' => Mage::helper('ddg')->__('Date')),
            array('value' => 'Boolean', 'label' => Mage::helper('ddg')->__('Yes/No'))
        );

        return $dataType;
    }

}