<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Campaign_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    /**
	 *   Construct.
	 */
    public function __construct()
    {
        parent::__construct();
        $this->_blockGroup = 'ddg_automation';
        $this->_controller = 'adminhtml_campaign';
        $this->_mode = 'edit';
        $this->_updateButton('save', 'label', Mage::helper('ddg')->__('Save Campaign'));
        $this->_updateButton('delete', 'label', Mage::helper('ddg')->__('Delete Campaign'));
        $this->_addButton('saveandcontinue', array(
            'label'        => Mage::helper('ddg')->__('Save And Continue Edit'),
            'onclick'    => 'saveAndContinueEdit()',
            'class'        => 'save',
        ), -100);
        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }";
    }

	/**
	 * Header text for the campaign.
	 * @return string
	 */
    public function getHeaderText()
    {
        if ( Mage::registry('email_campaign') && Mage::registry('email_campaign')->getId() ) {
            return Mage::helper('ddg')->__("Edit Campaign '%s'", $this->htmlEscape(Mage::registry('email_campaign')->getContact()));
        } else {
            return Mage::helper('ddg')->__('Add Contact');
        }
    }
}