<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Importer;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var object
     */
    protected $_gridFactory;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory
     */
    protected $_importerFactory;
    /**
     * @var array
     */
    protected $statusOptions;
    /**
     * @var array
     */
    protected $modeOptions;

    /**
     * Grid constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Importer\ModeFactory   $modeFactory
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Importer\StatusFactory $statusFactory
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory     $gridFactory
     * @param \Magento\Backend\Block\Template\Context                              $context
     * @param \Magento\Backend\Helper\Data                                         $backendHelper
     * @param array                                                                $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Importer\ModeFactory $modeFactory,
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Importer\StatusFactory $statusFactory,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory $gridFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->_importerFactory = $gridFactory;
        $this->statusOptions = $statusFactory->create()->getOptions();
        $this->modeOptions = $modeFactory->create()->getOptions();

        parent::__construct($context, $backendHelper, $data);
    }

    /**
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
        $this->setCollection($this->_importerFactory->create());

        return parent::_prepareCollection();
    }

    /**
     * Prepare the grid collumns.
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('id', [
            'header' => __('ID'),
            'align' => 'left',
            'index' => 'id',
            'type' => 'number',
            'escape' => true,
        ])->addColumn('import_type', [
            'header' => __('Import Type'),
            'index' => 'import_type',
            'type' => 'text',
            'escape' => true,
        ])->addColumn('import_status', [
            'header' => __('Import Status'),
            'index' => 'import_status',
            'type' => 'options',
            'escape' => true,
            'options' => $this->statusOptions,
        ])->addColumn('message', [
            'header' => __('Error Message'),
            'index' => 'message',
            'type' => 'text',
            'escape' => true,
        ])->addColumn('import_mode', [
            'header' => __('Import Mode'),
            'index' => 'import_mode',
            'type' => 'options',
            'escape' => true,
            'options' => $this->modeOptions,
        ])->addColumn('import_id', [
            'header' => __('Import ID'),
            'index' => 'import_id',
            'type' => 'text',
            'escape' => true,
        ])->addColumn('import_started', [
            'header' => __('Imported At'),
            'align' => 'center',
            'index' => 'import_started',
            'type' => 'datetime',
            'escape' => true,
        ])->addColumn('import_finished', [
            'header' => __('Last Check Time'),
            'align' => 'center',
            'index' => 'import_finished',
            'type' => 'datetime',
            'escape' => true,
        ])->addColumn('created_at', [
            'header' => __('Created At'),
            'align' => 'center',
            'index' => 'created_at',
            'type' => 'datetime',
            'escape' => true,
        ])->addColumn('updated_at', [
            'header' => __('Updated At'),
            'align' => 'center',
            'index' => 'updated_at',
            'type' => 'datetime',
            'escape' => true,
        ]);

        return parent::_prepareColumns();
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
                'confirm' => __('Are you sure?'),
            ]
        );
        $this->getMassactionBlock()->addItem(
            'resend',
            [
                'label' => __('Reset'),
                'url' => $this->getUrl('*/*/massResend'),
                'confirm' => __('Are you sure?'),
            ]
        );

        return $this;
    }
}
