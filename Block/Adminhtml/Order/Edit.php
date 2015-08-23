<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Order;

class Edit extends \Magento\Backend\Block\Widget\Form\Container {

	protected $_coreRegistry = null;

	/**
	 *      * Initialize blog post edit block
	 *      *
	 *      * @return void
	 *      */
	protected function _construct()
	{
		$this->_objectId   = 'email_order_id';
		$this->_blockGroup = 'dotdigitalgroup_email';
		$this->_controller = 'adminhtml_order';

		parent::_construct();

		$this->buttonList->update( 'save', 'label', __( 'Save Order' ) );
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
		$this->buttonList->update( 'delete', 'label', __( 'Delete Order' ) );
	}

	public function getHEaderText()
	{
		if ($this->_coreRegistry->registry('email_order_data')){
			return __("Edit Order'%1'", $this->escapeHtml($this->_coreRegistry->registry('email_order_data')->getTitle()));
		} else {
			return __('New Order');
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