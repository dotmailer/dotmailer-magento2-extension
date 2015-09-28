<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Campaign;

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
		\Dotdigitalgroup\Email\Model\Resource\Campaign\CollectionFactory $gridFactory,
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Backend\Helper\Data $backendHelper,
		\Magento\Framework\Module\Manager $moduleManager,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	) {
		$this->_campaignFactory = $gridFactory;
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
		$this->setId('id');
		$this->setDefaultSort('entity_id');
		$this->setDefaultDir('DESC');
	}

	/**
	 * @return $this
	 */
	protected function _prepareCollection()
	{
		$collection = $this->_campaignFactory->create();
		$this->setCollection($collection);

		return parent::_prepareCollection();
	}

	protected function _prepareColumns()
	{
		$this->addColumn('id', array(
			'header'        => __('Campaign ID'),
			'width'         => '20px',
			'index'         => 'campaign_id',
			'type'          => 'number',
			'truncate'      => 50,
			'escape'        => true
		))->addColumn('customer_id', array(
			'header'        => __('Customer ID'),
			'align'         => 'left',
			'width'         => '50px',
			'index'         => 'customer_id',
			'type'          => 'number',
			'escape'        => true
		))->addColumn('email', array(
			'header'        => __('Email'),
			'align'         => 'left',
			'width'         => '50px',
			'index'         => 'email',
			'type'          => 'text',
			'escape'        => true
		))->addColumn('is_sent', array(
			'header'        => __('Is Sent'),
			'align'         => 'center',
			'width'         => '20px',
			'index'         => 'is_sent',
			'escape'        => true,
			'type'          => 'options',
			'renderer'     => 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
			'options'       => [
				'1'    => 'Is Send',
				'null' => 'Not Send'
			],
			'filter_condition_callback' => array($this, 'filterCallbackContact')
		))->addColumn('message', array(
			'header'		=> __('Send Message'),
			'align'		=> 'left',
			'width'		=> '300px',
			'index'     => 'message',
			'type'      => 'text',
			'escape'    => true
		))->addColumn('event_name', array(
			'header'        => __('Event Name'),
			'align'         => 'left',
			'index'         => 'event_name',
			'width'		    => '100px',
			'type'          => 'string',
			'escape'        => true
		))->addColumn('quote_id', array(
			'header'        => __('Quote Id'),
			'align'         => 'left',
			'width'         => '50px',
			'index'         => 'quote_id',
			'type'          => 'number',
			'escape'        => true
		))->addColumn('sent_at', array(
			'header'    => __('Sent At'),
			'align'     => 'center',
			'width'     => '100px',
			'index'     => 'sent_at',
			'type'     => 'datetime',
			'escape'   => true
		))->addColumn('created_at', array(
			'header'    => __('Created At'),
			'align'     => 'center',
			'width'     => '100px',
			'index'     => 'created_at',
			'type'      => 'datetime',
			'escape'    => true
		))->addColumn('updated_at', array(
			'header'    => __('Updated At'),
			'align'     => 'center',
			'width'     => '100px',
			'index'     => 'updated_at',
			'type'      => 'datetime',
			'escape'    => true
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

		//$this->getMassactionBlock()->addItem('resend', ['label'=>  __('Resend'),'url'=> $this->getUrl('*/*/massResend')]);
		//$this->getMassactionBlock()->addItem('re-create', ['label'=> __('Recreate'),'url'=>$this->getUrl('*/*/massRecreate')]);
		return $this;
	}



	public function getRowUrl($row)
	{
		return $this->getUrl(
			'dotdigitalgroup_email/*/edit',
			['id' => $row->getId()]
		);
	}

}
