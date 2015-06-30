<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Importer_Status
{
    /**
     * Contact imported options.
     *
     * @return array
     */
    public function getOptions()
    {
        return array(
            Dotdigitalgroup_Email_Model_Importer::NOT_IMPORTED => Mage::helper('ddg')->__('Not Imported'),
            Dotdigitalgroup_Email_Model_Importer::IMPORTING => Mage::helper('ddg')->__('Importing'),
            Dotdigitalgroup_Email_Model_Importer::IMPORTED => Mage::helper('ddg')->__('Imported'),
            Dotdigitalgroup_Email_Model_Importer::FAILED => Mage::helper('ddg')->__('Failed'),
        );
    }
}