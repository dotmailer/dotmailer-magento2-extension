<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Cron;

use Magento\Backend\Block\Widget\Grid as WidgetGrid;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
	/**
	 * @var \Magento\Framework\Module\Manager
	 */
	protected $moduleManager;
	protected $_gridFactory;
	protected $_objectManager;
	protected $_gridCollection;

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
		\Magento\Cron\Model\Resource\Schedule\CollectionFactory $gridFactory,
		\Magento\Framework\Module\Manager $moduleManager,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	) {
		$this->_gridCollection = $gridFactory;
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
		$this->setId('schedule_id');
		$this->setDefaultSort('schedule_id');
		$this->setDefaultDir('DESC');
		$this->setSaveParametersInSession(true);
		$this->setUseAjax(true);
	}

	/**
	 * @return $this
	 */
	protected function _prepareCollection()
	{
		$collection = $this->_gridCollection->create();
		$this->setCollection($collection);

		parent::_prepareCollection();
		return $this;
	}

	/**
	 * Prepare the grid collumns.
	 * @return $this
	 */
	protected function _prepareColumns()
	{
		$this->addColumn('schedule_id', array(
			'header'        => __('Scheldule ID'),
			'align'         => 'left',
			'width'         => '50px',
			'index'         => 'schedule_id',
			'type'          => 'number',
			'escape'        => true
		))->addColumn('job_code', array(
			'header'        => __('Job Code'),
			'align'         => 'center',
			'width'         => '50px',
			'index'         => 'job_code',
			'type'          => 'options',
			'escape'        => true
		))->addColumn('status', array(
			'header'        => __('Modified'),
			'align'         => 'center',
			'width'         => '50px',
			'index'         => 'status',
			'type'          => 'options',
			'escape'        => true
		))->addColumn('messages', array(
			'header'        => __('Messages'),
			'align'         => 'center',
			'width'         => '50px',
			'index'         => 'messages',
			'type'          => 'options',
			'escape'        => true
		))->addColumn('created_at', array(
			'header'        => __('Created At'),
			'width'         => '50px',
			'align'         => 'center',
			'index'         => 'created_at',
			'type'          => 'datetime',
			'escape'        => true,
		))->addColumn('scheduled_at', array(
			'header'        => __('Schelduled At'),
			'width'         => '50px',
			'align'         => 'center',
			'index'         => 'scheduled_at',
			'type'          => 'datetime',
			'escape'        => true,
		))->addColumn('executed_at', array(
			'header'        => __('Executed At'),
			'width'         => '50px',
			'align'         => 'center',
			'index'         => 'executed_at',
			'type'          => 'datetime',
			'escape'        => true,
		))->addColumn('finished_at', array(
			'header'        => __('Finished At'),
			'width'         => '50px',
			'align'         => 'center',
			'index'         => 'finished_at',
			'type'          => 'datetime',
			'escape'        => true,
		));

		return parent::_prepareColumns();
	}


	/**
	 * @return $this
	 */
	protected function _prepareMassaction()
	{
		$this->setMassactionIdField('schedule_id');
		$this->getMassactionBlock()->setFormFieldName('schedule_id');

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
			['schedule_id' => $row->getSchelduleId()]
		);
	}

}
