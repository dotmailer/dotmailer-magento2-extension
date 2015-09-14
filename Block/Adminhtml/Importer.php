<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml;

class Importer extends \Magento\Backend\Block\Widget\Container
{
	/**
	 * Class constructor
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->addData(
			[
				\Magento\Backend\Block\Widget\Container::PARAM_CONTROLLER => 'adminhtml_importer',
				\Magento\Backend\Block\Widget\Grid\Container::PARAM_BLOCK_GROUP => 'Dotdigitalgroup_Email',
				\Magento\Backend\Block\Widget\Container::PARAM_HEADER_TEXT => __('Importer'),
			]
		);
		parent::_construct();
	}
	/**
	 * Prepare button and gridCreate Grid , edit/add grid row and installer in Magento2
	 *
	 * @return \Magento\Catalog\Block\Adminhtml\Product
	 */
	protected function _prepareLayout()
	{

		$this->setChild(
			'grid',
			$this->getLayout()->createBlock('Dotdigitalgroup\Email\Block\Adminhtml\Importer\Grid', 'importer.view.grid')
		);
		return parent::_prepareLayout();
	}

	/**
	 * Render grid
	 *
	 * @return string
	 */
	public function getGridHtml()
	{
		return $this->getChildHtml('grid');
	}
}
