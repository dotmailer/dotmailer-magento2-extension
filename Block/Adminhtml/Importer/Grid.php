<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Importer;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
	/**
	 * @var \Magento\Framework\Module\Manager
	 */
	protected $moduleManager;
	protected $_gridFactory;
	protected $_objectManager;
	protected $_importerFactory;
	protected $statusOptions;
	protected $modeOptions;


	/**
	 * @param \Magento\Backend\Block\Template\Context $context
	 * @param \Magento\Backend\Helper\Data $backendHelper
	 * @param \Magento\Framework\Module\Manager $moduleManager
	 * @param array $data
	 *
	 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
	 */
	public function __construct(
		\Dotdigitalgroup\Email\Model\Adminhtml\Source\Importer\ModeFactory $modeFactory,
		\Dotdigitalgroup\Email\Model\Adminhtml\Source\Importer\StatusFactory $statusFactory,
		\Dotdigitalgroup\Email\Model\Resource\Importer\CollectionFactory $gridFactory,
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Backend\Helper\Data $backendHelper,
		\Magento\Framework\Module\Manager $moduleManager,
		\Magento\Framework\ObjectManagerInterface $objectManagerInterface,
		array $data = []
	) {
		$this->_importerFactory = $gridFactory;
		$this->_objectManager = $objectManagerInterface;
		$this->moduleManager = $moduleManager;
		$this->statusOptions = $statusFactory->create()->getOptions();
		$this->modeOptions = $modeFactory->create()->getOptions();

		parent::__construct($context, $backendHelper, $data);
	}

	/**
	 * @return void
	 */
	protected function _construct()
	{
		parent::_construct();
		$this->setId('importer');
		$this->setDefaultSort('id');
		$this->setDefaultDir('DESC');
	}

	/**
	 * @return $this
	 */
	protected function _prepareCollection()
	{
		$collection = $this->_importerFactory->create();
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
		$this->addColumn('id', array(
			'header' => __('ID'),
			'align' => 'left',
			'index' => 'id',
			'type' => 'number',
			'escape' => true
		))->addColumn('import_type', array(
			'header' => __('Import Type'),
			'width' => '50px',
			'index' => 'import_type',
			'type' => 'text',
			'escape' => true
		))->addColumn('import_status', array(
			'header' => __('Import Status'),
			'width' => '50px',
			'index' => 'import_status',
			'type' => 'options',
			'escape' => true,
			'options' => $this->statusOptions,
		))->addColumn('message', array(
			'header' => __('Error Message'),
			'index' => 'message',
			'type' => 'text',
			'escape' => true
		))->addColumn('import_mode', array(
			'header' => __('Import Mode'),
			'width' => '50px',
			'index' => 'import_mode',
			'type' => 'options',
			'escape' => true,
			'options' => $this->modeOptions,
		))->addColumn('import_id', array(
			'header' => __('Import ID'),
			'width' => '50px',
			'index' => 'import_id',
			'type' => 'text',
			'escape' => true
		))->addColumn('import_started', array(
			'header' => __('Imported At'),
			'width' => '50px',
			'align' => 'center',
			'index' => 'import_started',
			'type' => 'datetime',
			'escape' => true
		))->addColumn('import_finished', array(
			'header' => __('Last Check Time'),
			'width' => '50px',
			'align' => 'center',
			'index' => 'import_finished',
			'type' => 'datetime',
			'escape' => true
		))->addColumn('script', array(
			'header' => __('Script'),
			'renderer' => 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Script',
			'column_css_class' => 'no-display',
			'header_css_class' => 'no-display'
		))->addColumn('created_at', array(
			'header' => __('Created At'),
			'width' => '50px',
			'align' => 'center',
			'index' => 'created_at',
			'type' => 'datetime',
			'escape' => true
		))->addColumn('updated_at', array(
			'header' => __('Updated At'),
			'width' => '50px',
			'align' => 'center',
			'index' => 'updated_at',
			'type' => 'datetime',
			'escape' => true
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
