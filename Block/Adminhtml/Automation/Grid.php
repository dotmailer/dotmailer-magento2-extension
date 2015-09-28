<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Automation;

use Magento\Backend\Block\Widget\Grid as WidgetGrid;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
	/**
	 * @var \Magento\Framework\Module\Manager
	 */
	protected $moduleManager;
	protected $_gridFactory;
	protected $_objectManager;
	protected $_automationFactory;

	/**
	 * @param \Magento\Backend\Block\Template\Context $context
	 * @param \Magento\Backend\Helper\Data $backendHelper
	 * @param \Magento\Framework\Module\Manager $moduleManager
	 * @param array $data
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
	 */
	public function __construct(
		\Dotdigitalgroup\Email\Model\Resource\Automation\CollectionFactory $gridFactory,
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Backend\Helper\Data $backendHelper,
		\Magento\Framework\Module\Manager $moduleManager,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	) {
		$this->_automationFactory = $gridFactory;
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
		$this->setDefaultSort('id');
		$this->setDefaultDir('DESC');
	}

	/**
	 * @return $this
	 */
	protected function _prepareCollection()
	{
		$collection = $this->_automationFactory->create();
		$this->setCollection($collection);
		$this->setDefaultSort('updated_at');
		$this->setDefaultDir('DESC');
		return parent::_prepareCollection();
	}

	protected function _prepareColumns()
	{
		$this->addColumn( 'id', array(
			'header' => __( 'ID' ),
			'width'  => '20px',
			'index'  => 'id',
			'type'   => 'number',
			'escape' => true,
		) )->addColumn( 'program_id', array(
			'header' => __( 'Program ID' ),
			'align'  => 'center',
			'width'  => '50px',
			'index'  => 'program_id',
			'type'   => 'number',
			'escape' => true,
		) )->addColumn( 'automation_type', array(
			'header' => __( 'Automation Type' ),
			'align'  => 'right',
			'width'  => '50px',
			'index'  => 'automation_type',
			'type'   => 'text',
			'escape' => true
		) )->addColumn( 'enrolment_status', array(
			'header'  => __( 'Enrollment Status' ),
			'align'   => 'left',
			'width'   => '20px',
			'index'   => 'enrolment_status',
			'type'    => 'options',
			'options' => [
				'pending'                   => 'Pending',
				'Active'                    => 'Active',
				'Draft'                     => 'Draft',
				'Deactivated'               => 'Deactivated',
				'ReadOnly'                  => 'ReadOnly',
				'NotAvailableInThisVersion' => 'NotAvailableInThisVersion',
				'Failed'                    => 'Failed'
			],
			'escape'  => true
		) )->addColumn( 'email', array(
			'header' => __( 'Email' ),
			'width'  => '50px',
			'align'  => 'right',
			'index'  => 'email',
			'type'   => 'text',
			'escape' => true,
		) )->addColumn( 'type_id', array(
			'header' => __( 'Type ID' ),
			'align'  => 'center',
			'width'  => '50px',
			'index'  => 'type_id',
			'type'   => 'number',
			'escape' => true,
		) )->addColumn( 'message', array(
			'header' => __( 'Message' ),
			'width'  => '50px',
			'align'  => 'right',
			'index'  => 'message',
			'type'   => 'text',
			'escape' => true
		) )->addColumn( 'created_at', array(
			'header' => __( 'Created_at' ),
			'width'  => '20px',
			'align'  => 'center',
			'index'  => 'created_at',
			'escape' => true,
			'type'   => 'date'
		) )->addColumn( 'updated_at', array(
			'header' => __( 'Updated at' ),
			'align'  => 'center',
			'index'  => 'updated_at',
			'escape' => true,
			'type'   => 'datetime'
		) )->addColumn( 'website_id', array(
			'header'  => __( 'Website' ),
			'align'   => 'center',
			'type'    => 'options',
			'options' => $this->_objectManager->get( 'Magento\Store\Model\System\Store' )->getWebsiteOptionHash( true ),
			'index'   => 'website_id',
		) );


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

		$this->getMassactionBlock()->addItem('delete', array(
			'label'=> __('Delete'),
			'url'  => $this->getUrl('*/*/massDelete'),
			'confirm'  => __('Are you sure?')));

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
