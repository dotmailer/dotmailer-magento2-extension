<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Automation;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var
     */
    protected $_gridFactory;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory
     */
    protected $_automationFactory;

    /**
     * Grid constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory $gridFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManagerInterface
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory $gridFactory,
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
     * Constructor.
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

    /**
     * @return $this
     *
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('id', [
            'header' => __('ID'),
            'index' => 'id',
            'type' => 'number',
            'escape' => true,
        ])->addColumn('program_id', [
            'header' => __('Program ID'),
            'align' => 'center',
            'index' => 'program_id',
            'type' => 'number',
            'escape' => true,
        ])->addColumn('automation_type', [
            'header' => __('Automation Type'),
            'align' => 'right',
            'index' => 'automation_type',
            'type' => 'text',
            'escape' => true,
        ])->addColumn('enrolment_status', [
            'header' => __('Enrollment Status'),
            'align' => 'left',
            'index' => 'enrolment_status',
            'type' => 'options',
            'options' => [
                'pending' => 'Pending',
                'suppressed' => 'Suppressed',
                'Active' => 'Active',
                'Draft' => 'Draft',
                'Deactivated' => 'Deactivated',
                'ReadOnly' => 'ReadOnly',
                'NotAvailableInThisVersion' => 'NotAvailableInThisVersion',
                'Failed' => 'Failed',
            ],
            'escape' => true,
        ])->addColumn('email', [
            'header' => __('Email'),
            'align' => 'right',
            'index' => 'email',
            'type' => 'text',
            'escape' => true,
        ])->addColumn('type_id', [
            'header' => __('Type ID'),
            'align' => 'center',
            'index' => 'type_id',
            'type' => 'number',
            'escape' => true,
        ])->addColumn('message', [
            'header' => __('Message'),
            'align' => 'right',
            'index' => 'message',
            'type' => 'text',
            'escape' => true,
        ])->addColumn('created_at', [
            'header' => __('Created At'),
            'align' => 'center',
            'index' => 'created_at',
            'escape' => true,
            'type' => 'datetime',
        ])->addColumn('updated_at', [
            'header' => __('Updated At'),
            'align' => 'center',
            'index' => 'updated_at',
            'escape' => true,
            'type' => 'datetime',
        ])->addColumn('website_id', [
            'header' => __('Website'),
            'align' => 'center',
            'type' => 'options',
            'options' => $this->_objectManager->get('Magento\Store\Model\System\Store')
                ->getWebsiteOptionHash(true),
            'index' => 'website_id',
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

        $this->getMassactionBlock()->addItem('delete', [
            'label' => __('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => __('Are you sure?'),
        ]);

        return $this;
    }
}
