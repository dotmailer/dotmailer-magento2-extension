<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Quote;

class Edit extends \Magento\Backend\Block\Widget\Form\Container {

	protected $_coreRegistry = null;

	/**
	 *      * Initialize blog post edit block
	 *      *
	 *      * @return void
	 *      */
	protected function _construct()
	{
		$this->_objectId   = 'email_quote_id';
		$this->_blockGroup = 'dotdigitalgroup_email';
		$this->_controller = 'adminhtml_quote';

		parent::_construct();

		$this->buttonList->update( 'save', 'label', __( 'Save Quote' ) );
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
		$this->buttonList->update( 'delete', 'label', __( 'Delete Quote' ) );
	}

	public function getHEaderText()
	{
		if ($this->_coreRegistry->registry('email_quote_data')){
			return __("Edit Quote'%1'", $this->escapeHtml($this->_coreRegistry->registry('email_quote_data')->getTitle()));
		} else {
			return __('New Quote');
		}
	}

	protected function _getSaveAndContinueUrl()
	{
		return $this->getUrl('*/*/save', ['_current' => true, 'back' => 'edit', 'active_tab' => '{{tab_id']);
	}

	protected function _prepareLayout()
	{
		return parent::_prepareLayout();
	}



}