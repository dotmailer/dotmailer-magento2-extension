<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Wishlist;

use Magento\Backend\Block\Widget\Grid as WidgetGrid;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
	/**
	 * @var \Magento\Framework\Module\Manager
	 */
	protected $moduleManager;
	protected $_gridFactory;
	protected $_objectManager;
	protected $_wishlistFactory;

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
		\Dotdigitalgroup\Email\Model\Resource\Wishlist\CollectionFactory $gridFactory,
		\Magento\Framework\Module\Manager $moduleManager,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	) {
		$this->_wishlistFactory = $gridFactory;
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
		$this->setId('wishlist');
		$this->setDefaultSort('wishlist_id');
		$this->setDefaultDir('DESC');
	}

	/**
	 * @return $this
	 */
	protected function _prepareCollection()
	{
		$collection = $this->_wishlistFactory->create();
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
		$this->addColumn('wishlist_id', array(
			'header'        => __('Wishlist ID'),
			'align'         => 'left',
			'width'         => '50px',
			'index'         => 'wishlist_id',
			'type'          => 'number',
			'escape'        => true
		))->addColumn('customer_id', array(
			'header'        => __('Customer ID'),
			'align'         => 'left',
			'width'         => '50px',
			'index'         => 'customer_id',
			'type'          => 'number',
			'escape'        => true
		))->addColumn('item_count', array(
			'header'        => __('Item Count'),
			'align'         => 'left',
			'width'         => '50px',
			'index'         => 'item_count',
			'type'          => 'number',
			'escape'        => true
		))->addColumn('wishlist_imported', array(
			'header'        => _('Wishlist Imported'),
			'align'         => 'center',
			'width'         => '50px',
			'index'         => 'wishlist_imported',
			'type'          => 'options',
			'escape'        => true,
			'renderer'		=> 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
			'options'       => $this->_objectManager->create('Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\Imported')->getOptions(),
			'filter_condition_callback' => array($this, 'filterCallbackContact')
		))->addColumn('wishlist_modified', array(
			'header'        => __('Wishlist Modified'),
			'align'         => 'center',
			'width'         => '50px',
			'index'         => 'wishlist_modified',
			'type'          => 'options',
			'escape'        => true,
			'renderer'		=> 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
			'options'       => $this->_objectManager->create('Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\Imported')->getOptions(),
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
