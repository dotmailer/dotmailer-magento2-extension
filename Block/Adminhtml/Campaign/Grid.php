<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Campaign;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $moduleManager;

    protected $_gridFactory;

    protected $_objectManager;

    protected $_campaignFactory;

    /**
     * Grid constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $gridFactory
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManagerInterface
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory $gridFactory,
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
     * Constructor.
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
        $this->addColumn('id', [
            'header' => __('Campaign ID'),
            'width' => '20px',
            'index' => 'campaign_id',
            'type' => 'number',
            'truncate' => 50,
            'escape' => true,
        ])->addColumn('customer_id', [
            'header' => __('Customer ID'),
            'align' => 'left',
            'width' => '50px',
            'index' => 'customer_id',
            'type' => 'number',
            'escape' => true,
        ])->addColumn('email', [
            'header' => __('Email'),
            'align' => 'left',
            'width' => '50px',
            'index' => 'email',
            'type' => 'text',
            'escape' => true,
        ])->addColumn('is_sent', [
            'header' => __('Is Sent'),
            'align' => 'center',
            'width' => '20px',
            'index' => 'is_sent',
            'escape' => true,
            'type' => 'options',
            'renderer' => 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
            'options' => [
                '1' => 'Is Send',
                'null' => 'Not Send',
            ],
            'filter_condition_callback' => [$this, 'filterCallbackContact'],
        ])->addColumn('send_id', [
                'header' => __('Send Id'),
                'align' => 'left',
                'width' => '300px',
                'index' => 'send_id',
                'type' => 'text',
                'escape' => true
            ]
        )->addColumn('message', [
            'header' => __('Send Message'),
            'align' => 'left',
            'width' => '300px',
            'index' => 'message',
            'type' => 'text',
            'escape' => true,
        ])->addColumn('event_name', [
            'header' => __('Event Name'),
            'align' => 'left',
            'index' => 'event_name',
            'width' => '100px',
            'type' => 'string',
            'escape' => true,
        ])->addColumn('quote_id', [
            'header' => __('Quote ID'),
            'align' => 'left',
            'width' => '50px',
            'index' => 'quote_id',
            'type' => 'number',
            'escape' => true,
        ])->addColumn('sent_at', [
            'header' => __('Sent At'),
            'align' => 'center',
            'width' => '100px',
            'index' => 'sent_at',
            'type' => 'datetime',
            'escape' => true,
        ])->addColumn('created_at', [
            'header' => __('Created At'),
            'align' => 'center',
            'width' => '100px',
            'index' => 'created_at',
            'type' => 'datetime',
            'escape' => true,
        ])->addColumn('updated_at', [
            'header' => __('Updated At'),
            'align' => 'center',
            'width' => '100px',
            'index' => 'updated_at',
            'type' => 'datetime',
            'escape' => true,
        ]);

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
        $field = $column->getFilterIndex() ? $column->getFilterIndex()
            : $column->getIndex();
        $value = $column->getFilter()->getValue();
        if ($value == 'null') {
            $collection->addFieldToFilter($field, ['null' => true]);
        } else {
            $collection->addFieldToFilter($field, ['notnull' => true]);
        }
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
            ]
        );

        return $this;
    }
}
