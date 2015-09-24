<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Contact;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
	protected $_coreRegistry = null;

	/**
	 *      *
	 *      * @return void
	 *      */
	protected function _construct()
	{
		$this->_objectId   = 'email_contact_id';
		$this->_blockGroup = 'Dotdigitalgroup_Email';
		$this->_controller = 'dotdigitalgroup_email_contact';

		parent::_construct();

		$this->buttonList->update( 'save', 'label', __( 'Save Contact' ) );
		$this->buttonList->add(
			'saveandcontinue',
			[
				'label'          => __( 'Save and Continue Edit' ),
				'class'          => 'save',
				'data_attribute' => [
					'mage-init' => [
						'button' => [ 'event' => 'saveAndContinueEdit', 'target' => '#edit_form' ],
					],
				]
			],
			- 100
		);
		$this->buttonList->update( 'delete', 'label', __( 'Delete Contact' ) );
	}

	public function getHEaderText()
	{
		if ($this->_coreRegistry->registry('email_contact_data')){
			return __("Edit Contact'%1'", $this->escapeHtml($this->_coreRegistry->registry('email_contact_data')->getTitle()));
		} else {
			return __('New Contact');
		}
	}

	protected function _getSaveAndContinueUrl()
	{
		return $this->getUrl('dotdigitalgroup_email/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id']);
	}

	protected function _prepareLayout()
	{
		return parent::_prepareLayout();
	}



}