<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Cron;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $moduleManager;
    protected $_gridFactory;
    protected $_campaignFactory;

    /**
     * Grid constructor.
     *
     * @param \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory $gridFactory
     * @param \Magento\Backend\Block\Template\Context                      $context
     * @param \Magento\Backend\Helper\Data                                 $backendHelper
     * @param \Magento\Framework\Module\Manager                            $moduleManager
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory $gridFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_gridFactory   = $gridFactory;
        $this->moduleManager  = $moduleManager;

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
        $this->addColumn(
            'schedule_id', array(
                'header'   => __('ID'),
                'width'    => '20px',
                'index'    => 'schedule_id',
                'type'     => 'number',
                'truncate' => 50,
                'escape'   => true
            )
        )->addColumn(
            'job_code', array(
                'header' => __('Job Code'),
                'align'  => 'left',
                'width'  => '50px',
                'index'  => 'job_code',
                'type'   => 'string',
                'escape' => true
            )
        )->addColumn(
            'status', array(
                'header' => __('Status'),
                'align'  => 'left',
                'width'  => '50px',
                'index'  => 'status',
                'type'   => 'string',
                'escape' => true
            )
        )->addColumn(
            'messages', array(
                'header' => __('Message'),
                'align'  => 'center',
                'index'  => 'messages',
                'type'   => 'text',
                'escape' => true
            )
        )->addColumn(
            'created_at', array(
                'header' => __('Created At'),
                'align'  => 'left',
                'index'  => 'created_at',
                'type'   => 'datetime',
                'escape' => true
            )
        )->addColumn(
            'scheduled_at', array(
                'header' => __('Scheduled At'),
                'align'  => 'left',
                'index'  => 'scheduled_at',
                'type'   => 'datetime',
                'escape' => true
            )
        )->addColumn(
            'executed_at', array(
                'header' => __('Executed At'),
                'align'  => 'left',
                'index'  => 'executed_at',
                'type'   => 'datetime',
                'escape' => true
            )
        )->addColumn(
            'finished_at', array(
                'header' => __('Finished At'),
                'align'  => 'left',
                'index'  => 'finished_at',
                'type'   => 'datetime',
                'escape' => true
            )
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('id');
        $this->getMassactionBlock()->addItem(
            'delete', array(
                'label'   => __('Delete'),
                'url'     => $this->getUrl('*/*/massDelete'),
                'confirm' => __('Are you sure?')
            )
        );

        return $this;
    }

}
