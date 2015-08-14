<?php

class Dotdigitalgroup_Email_Model_Adminhtml_Source_Importer_Mode
{
    /**
     * Contact imported options.
     *
     * @return array
     */
    public function getOptions()
    {
        return array(
            Dotdigitalgroup_Email_Model_Importer::MODE_BULK => Mage::helper('ddg')->__(Dotdigitalgroup_Email_Model_Importer::MODE_BULK),
            Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE => Mage::helper('ddg')->__(Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE),
            Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE_DELETE => Mage::helper('ddg')->__(Dotdigitalgroup_Email_Model_Importer::MODE_SINGLE_DELETE),
            Dotdigitalgroup_Email_Model_Importer::MODE_CONTACT_DELETE => Mage::helper('ddg')->__(Dotdigitalgroup_Email_Model_Importer::MODE_CONTACT_DELETE)
        );
    }
}