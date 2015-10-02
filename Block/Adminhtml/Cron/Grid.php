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
	protected $_campaignFactory;

	/**
	 * @param \Magento\Backend\Block\Template\Context $context
	 * @param \Magento\Backend\Helper\Data $backendHelper
	 * @param \Magento\Framework\Module\Manager $moduleManager
	 * @param array $data
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
	 */
	public function __construct(
		\Magento\Cron\Model\Resource\Schedule\CollectionFactory $gridFactory,
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Backend\Helper\Data $backendHelper,
		\Magento\Framework\Module\Manager $moduleManager,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	) {
		$this->_gridFactory = $gridFactory;
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
	}

	/**
	 * @return $this
	 */
	protected function _prepareCollection()
	{
		$collection = $this->_gridFactory->create();
		$this->setCollection($collection);

		return parent::_prepareCollection();
	}

	protected function _prepareColumns()
	{
		$this->addColumn('schedule_id', array(
			'header'        => __('ID'),
			'width'         => '20px',
			'index'         => 'schedule_id',
			'type'          => 'number',
			'truncate'      => 50,
			'escape'        => true
		))->addColumn('job_code', array(
			'header'        => __('Job Code'),
			'align'         => 'left',
			'width'         => '50px',
			'index'         => 'job_code',
			'type'          => 'string',
			'escape'        => true
		))->addColumn('status', array(
			'header'        => __('Status'),
			'align'         => 'left',
			'width'         => '50px',
			'index'         => 'status',
			'type'          => 'string',
			'escape'        => true
		))->addColumn('messages', array(
			'header'        => __('Messages'),
			'align'         => 'center',
			'index'         => 'messages',
			'type'          => 'text',
			'escape'        => true
		))->addColumn('created_at', array(
			'header'		=> __('Created At'),
			'align'		    => 'left',
			'index'         => 'created_at',
			'type'          => 'datetime',
			'escape'        => true
		))->addColumn('scheduled_at', array(
			'header'        => __('Scheduled At'),
			'align'         => 'left',
			'index'         => 'scheduled_at',
			'type'          => 'datetime',
			'escape'        => true
		))->addColumn('executed_at', array(
			'header'        => __('Executed At'),
			'align'         => 'left',
			'index'         => 'executed_at',
			'type'          => 'datetime',
			'escape'        => true
		));

		return parent::_prepareColumns();
	}


	protected function _prepareMassaction()
	{
		$this->setMassactionIdField('id');
		$this->getMassactionBlock()->setFormFieldName('id');
		$this->getMassactionBlock()->addItem('delete', array (
				'label'=> __('Delete'),
				'url'  => $this->getUrl('*/*/massDelete'),
				'confirm'  => __('Are you sure?')
			)
		);

		return $this;
	}

}
