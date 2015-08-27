<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Rules;

use Magento\Backend\Block\Widget\Grid as WidgetGrid;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
	/**
	 * @var \Magento\Framework\Module\Manager
	 */
	protected $moduleManager;
	protected $_gridFactory;
	protected $_objectManager;
	protected $_rulesFactory;

	/**
	 * @param \Magento\Backend\Block\Template\Context $context
	 * @param \Magento\Backend\Helper\Data $backendHelper
	 * @param \Magento\Framework\Module\Manager $moduleManager
	 * @param array $data
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
	 */
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Backend\Helper\Data $backendHelper,
		\Dotdigitalgroup\Email\Model\RulesFactory $gridFactory,
		\Magento\Framework\Module\Manager $moduleManager,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	) {
		$this->_rulesFactory = $gridFactory;
		$this->_objectManager = $objectManagerInterface;
		$this->moduleManager = $moduleManager;
		parent::__construct($context, $backendHelper, $data);
	}

	/**
	 * @return void
	 */
	protected function _construct()
	{
		parent::_construct();
		$this->setId('rules');
		$this->setDefaultSort('id');
		$this->setDefaultDir('DESC');
		$this->setSaveParametersInSession(true);
		$this->setUseAjax(true);
	}

	/**
	 * @return $this
	 */
	protected function _prepareCollection()
	{
		$collection = $this->_rulesFactory->create()->getCollection();
		$this->setCollection($collection);

		parent::_prepareCollection();
		return $this;
	}

	/**
	 * Add columns to grid
	 *
	 * @return $this
	 */
	protected function _prepareColumns()
	{
		$this->addColumn('rule_id', array(
			'header'    => __('ID'),
			'align'     =>'right',
			'width'     => '50px',
			'index'     => 'id',
		));

		$this->addColumn('name', array(
			'header'    => __('Rule Name'),
			'align'     =>'left',
			'width'     => '150px',
			'index'     => 'name',
		));

		$this->addColumn('type', array(
			'header'    => __('Rule Type'),
			'align'     => 'left',
			'width'     => '150px',
			'index'     => 'type',
			'type'      => 'options',
			'options'   => array(
				1 => 'Abandoned Cart Exclusion Rule',
				2 => 'Review Email Exclusion Rule',
			),
		));
		$this->addColumn('status', array(
			'header'    => __('Status'),
			'align'     => 'left',
			'width'     => '80px',
			'index'     => 'status',
			'type'      => 'options',
			'options'   => array(
				1 => 'Active',
				0 => 'Inactive',
			),
		));

		$this->addColumn('created_at', array(
			'header'    => __('Created At'),
			'align'     => 'left',
			'width'     => '120px',
			'type'      => 'datetime',
			'index'     => 'created_at',
		));

		$this->addColumn('updated_at', array(
			'header'    => __('Updated At'),
			'align'     => 'left',
			'width'     => '120px',
			'type'      => 'datetime',
			'index'     => 'updated_at',
		));


		parent::_prepareColumns();
		return $this;
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

	/**
	 * @return string
	 */
	public function getGridUrl()
	{
		return $this->getUrl('*/*/grid', ['_current' => true]);
	}


	public function getRowUrl($row)
	{
		return $this->getUrl(
			'*/*/edit',
			['id' => $row->getId()]
		);
	}

}
