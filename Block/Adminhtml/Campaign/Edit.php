<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Contact;

class Edit extends \Magento\Backend\Block\Widget\Form\Container {

	protected $_coreRegistry = null;

	/**
	 *      * Initialize blog post edit block
	 *      *
	 *      * @return void
	 *      */
	protected function _construct()
	{
		$this->_objectId   = 'email_campaign_id';
		$this->_blockGroup = 'dotdigitalgroup_email';
		$this->_controller = 'adminhtml_campaign';

		parent::_construct();

		$this->buttonList->update( 'save', 'label', __( 'Save Campaign' ) );
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
		$this->buttonList->update( 'delete', 'label', __( 'Delete Campaign' ) );
	}

	public function getHEaderText()
	{
		if ($this->_coreRegistry->registry('email_campaign')){
			return __("Edit Contact'%1'", $this->escapeHtml($this->_coreRegistry->registry('email_campaign')->getTitle()));
		} else {
			return __('New Campaign');
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