<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Automation;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
	protected $_coreRegistry = null;

	public function _construct()
    {
        parent::_construct();
        $this->_blockGroup = 'dotdigitalgroup_email';
        $this->_controller = 'adminhtml_automation';
        $this->buttonList->update('delete', 'label', __('Delete Contact'));
        $this->buttonList->add('saveandcontinue', array(
            'label'        => __('Save And Continue Edit'),
            'onclick'    => 'saveAndContinueEdit()',
            'class'        => 'save',
        ), -100);
        $this->_formScripts[] = "
            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }

    /**
	 * HEader text.
	 * @return string
	 */
    public function getHeaderText()
    {
        if ( $this->_coreRegistry->registry('automation_data') && $this->_coreRegistry->registry('contact_data')->getId() ) {
            return __("Edit Automation '%s'", $this->escapeHtml($this->_coreRegistry->registry('contact_data')->getContact()));
        } else {
            return __('Add Automation');
        }
    }
}