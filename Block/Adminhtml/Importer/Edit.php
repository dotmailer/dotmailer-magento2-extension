<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Importer;

class Edit extends \Magento\Backend\Block\Widget\Form\Container {

	protected $_coreRegistry = null;

	/**
	 *      * Initialize blog post edit block
	 *      *
	 *      * @return void
	 *      */
	protected function _construct()
	{
		$this->_objectId   = 'id';
		$this->_blockGroup = 'dotdigitalgroup_email';
		$this->_controller = 'adminhtml_importer';

		parent::_construct();

		$this->buttonList->update( 'save', 'label', __( 'Save Importer' ) );
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
		$this->buttonList->update( 'delete', 'label', __( 'Delete Importer' ) );
	}

	public function getHEaderText()
	{
		if ($this->_coreRegistry->registry('email_importer')){
			return __("Edit Contact'%1'", $this->escapeHtml($this->_coreRegistry->registry('email_importer')->getTitle()));
		} else {
			return __('New Importer');
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