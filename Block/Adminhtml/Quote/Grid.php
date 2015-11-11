<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Quote;

use Magento\Backend\Block\Widget\Grid as WidgetGrid;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
	/**
	 * @var \Magento\Framework\Module\Manager
	 */
	protected $moduleManager;


	protected $_gridFactory;
	protected $_quoteFactory;
	protected $_importedFactory;
	/**
	 * @param \Magento\Backend\Block\Template\Context $context
	 * @param \Magento\Backend\Helper\Data $backendHelper
	 * @param \Magento\Framework\Module\Manager $moduleManager
	 * @param array $data
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
	 */
	public function __construct(
		\Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory $importedFactory,
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Backend\Helper\Data $backendHelper,
		\Dotdigitalgroup\Email\Model\Resource\Quote\CollectionFactory $gridFactory,
		\Magento\Framework\Module\Manager $moduleManager,
		array $data = []
	) {
		$this->_importedFactory = $importedFactory;
		$this->_quoteFactory = $gridFactory;
		$this->moduleManager = $moduleManager;
		parent::__construct($context, $backendHelper, $data);
	}

	/**
	 * @return void
	 */
	protected function _construct()
	{
		parent::_construct();
		$this->setId('quote');
		$this->setDefaultSort('id');
		$this->setDefaultDir('DESC');
	}

	/**
	 * @return $this
	 */
	protected function _prepareCollection()
	{
		$this->setCollection($this->_quoteFactory->create());

		return parent::_prepareCollection();
	}

	/**
	 * @return $this
	 * @throws \Exception
	 */
	protected function _prepareColumns()
	{
		$this->addColumn('quote_id', array(
			'header'        => __('Quote ID'),
			'align'         => 'left',
			'width'         => '50px',
			'index'         => 'quote_id',
			'type'          => 'number',
			'escape'        => true
		))->addColumn('customer_id', array(
			'header'        => __('Customer ID'),
			'align'         => 'left',
			'width'         => '50px',
			'index'         => 'customer_id',
			'type'          => 'number',
			'escape'        => true
		))->addColumn('store_id', array(
			'header'        => __('Store ID'),
			'align'         => 'left',
			'width'         => '50px',
			'index'         => 'store_id',
			'type'          => 'number',
			'escape'        => true
		))->addColumn('imported', array(
			'header'        => __('Quote Imported'),
			'align'         => 'center',
			'width'         => '50px',
			'index'         => 'imported',
			'type'          => 'options',
			'escape'        => true,
			'renderer'		=> 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
			'options'       => $this->_importedFactory->create()->getOptions(),
			'filter_condition_callback' => array($this, 'filterCallbackContact')
		))->addColumn('modified', array(
			'header'        => __('Quote Modified'),
			'align'         => 'center',
			'width'         => '50px',
			'index'         => 'modified',
			'type'          => 'options',
			'escape'        => true,
			'renderer'		=> 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
			'options'       => $this->_importedFactory->create()->getOptions(),
			'filter_condition_callback' => array($this, 'filterCallbackContact')
		))->addColumn('created_at', array(
			'header'        => __('Created At'),
			'width'         => '50px',
			'align'         => 'center',
			'index'         => 'created_at',
			'type'          => 'datetime',
			'escape'        => true,
		))->addColumn('updated_at', array(
			'header'        => __('Updated At'),
			'width'         => '50px',
			'align'         => 'center',
			'index'         => 'updated_at',
			'type'          => 'datetime',
			'escape'        => true,
		));

		return parent::_prepareColumns();
	}
	/**
	 * Callback action for the imported subscribers/contacts.
	 *
	 * @param $collection
	 * @param $column
	 */
	public function filterCallbackContact($collection, $column)
	{
		$field = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
		$value = $column->getFilter()->getValue();
		if ($value == 'null') {
			$collection->addFieldToFilter($field, array('null' => true));
		} else {
			$collection->addFieldToFilter($field, array('notnull' => true));
		}
	}

	/**
	 * @return $this
	 */
	protected function _prepareMassaction()
	{
		$this->setMassactionIdField('id');
		$this->getMassactionBlock()->setFormFieldName('id');

		$this->getMassactionBlock()->addItem(
			'delete',
			[
				'label' => __('Delete'),
				'url' => $this->getUrl('*/*/massDelete'),
				'confirm' => __('Are you sure?')
			]
		);


		return $this;
	}

}
