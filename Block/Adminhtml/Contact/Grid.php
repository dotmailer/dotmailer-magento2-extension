<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Contact;

use Magento\Backend\Block\Widget\Grid as WidgetGrid;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
	/**
	 * @var \Magento\Framework\Module\Manager
	 */
	protected $moduleManager;


	protected $_gridFactory;


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
		\Dotdigitalgroup\Email\Model\ContactFactory $gridFactory,
		\Magento\Framework\Module\Manager $moduleManager,
		array $data = []
	) {
		$this->_contactFactory = $gridFactory;
		$this->moduleManager = $moduleManager;
		parent::__construct($context, $backendHelper, $data);
	}

	/**
	 * @return void
	 */
	protected function _construct()
	{
		parent::_construct();
		$this->setId('contact');
		$this->setDefaultSort('email_contact_id');
		$this->setDefaultDir('DESC');
		$this->setSaveParametersInSession(true);
		$this->setUseAjax(true);
		//$this->setVarNameFilter('grid_record');
	}

	/**
	 * @return $this
	 */
	protected function _prepareCollection()
	{
		$collection = $this->_contactFactory->create()->getCollection();
		$this->setCollection($collection);

		parent::_prepareCollection();
		return $this;
	}

	/**
	 * @return $this
	 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
	 */
	protected function _prepareColumns()
	{
		$this->addColumn(
			'email_contact_id',
			[
				'header' => __('Email Contact ID'),
				'type' => 'number',
				'index' => 'email_contact_id',
				'header_css_class' => 'col-id',
				'column_css_class' => 'col-id'
			]
		);
		$this->addColumn(
			'email',
			[
				'header' => __('Email'),
				'type'  => 'text',
				'index' => 'email',
				'class' => 'xxx'
			]
		);
		$this->addColumn(
			'customer_id',
			[
				'header' => __('Customer ID'),
				'type' => 'number',
				'index' => 'customer_id'
			]
		);
		$this->addColumn(
			'is_guest',
			[
				'header' => __('Is Guest'),
				'type' => 'options',
				'index' => 'is_guest',
				'options' => ['0' => 'Guest', '1' => 'Not Guest']
			]
		);
		$this->addColumn(
			'is_subscriber',
			[
				'header' => __('Is Subscriber'),
				'index' => 'is_subscriber',
				'type' => 'options',
				'options' => ['0' => 'Not Subscriber', '1' => 'Subscriber'],
				'escape' => true
			]
		);
		$this->addColumn('subscriber_status', [
			'header'        => 'Subscriber Status',
			'align'         => 'center',
			'width'         => '50px',
			'index'         => 'subscriber_status',
			'type'          => 'options',
			'options'       => [
				'1' => 'Subscribed',
				'2' => 'Not Active',
				'3' => 'Unsubscribed',
				'4' => 'Unconfirmed'
			],
			'escape'        => true,
		]);
		$this->addColumn(
			'website_id',
			[
				'header' => __('Website ID'),
				'index' => 'website_id'
			]
		);
		$this->addColumn(
			'subscriber_imported',
			[
				'header' => __('Subscriber Imported'),
				'index' => 'subscriber_imported'
			]
		);
		$this->addColumn(
			'suppressed',
			[
				'header' => __('Suppressed'),
				'index' => 'suppressed'
			]
		);

		$this->addColumn(
			'edit',
			[
				'header' => __('Edit'),
				'type' => 'action',
				'getter' => 'getId',
				'actions' => [
					[
						'caption' => __('Edit'),
						'url' => [
							'base' => '*/*/edit'
						],
						'field' => 'email_contact_id'
					]
				],
				'filter' => false,
				'sortable' => false,
				'index' => 'stores',
				'header_css_class' => 'col-action',
				'column_css_class' => 'col-action'
			]
		);

		$block = $this->getLayout()->getBlock('grid.bottom.links');
		if ($block) {
			$this->setChild('grid.bottom.links', $block);
		}

		return parent::_prepareColumns();
	}

	/**
	 * @return $this
	 */
	protected function _prepareMassaction()
	{
		$this->setMassactionIdField('email_contact_id');
		$this->getMassactionBlock()->setFormFieldName('email_contact_id');

		$this->getMassactionBlock()->addItem(
			'delete',
			[
				'label' => __('Delete'),
				'url' => $this->getUrl('dotdigitalgroup_email/*/massDelete'),
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
		return $this->getUrl('dotdigitalgroup_email/*/grid', ['_current' => true]);
	}


	public function getRowUrl($row)
	{
		return $this->getUrl(
			'dotdigitalgroup_email/*/edit',
			['email_contact_id' => $row->getId()]
		);
	}

}
