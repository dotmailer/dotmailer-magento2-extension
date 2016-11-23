<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Rules;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $_objectManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Rules\CollectionFactory
     */
    public $rulesFactory;

    /**
     * Grid constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Rules\CollectionFactory $gridFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManagerInterface
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Rules\CollectionFactory $gridFactory,
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface,
        array $data = []
    ) {
        $this->rulesFactory   = $gridFactory;
        $this->_objectManager = $objectManagerInterface;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('rules');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
    }

    /**
     * @return $this
     */
    public function _prepareCollection()
    {
        $collection = $this->rulesFactory->create();
        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    /**
     * Add columns to grid.
     *
     * @return $this
     */
    public function _prepareColumns()
    {
        $this->addColumn(
            'rule_id',
            [
            'header' => __('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'id',
            ]
        );

            $this->addColumn(
                'name',
                [
                'header' => __('Rule Name'),
                'align' => 'left',
                'width' => '150px',
                'index' => 'name',
                ]
            );

            $this->addColumn(
                'type',
                [
                'header' => __('Rule Type'),
                'align' => 'left',
                'width' => '150px',
                'index' => 'type',
                'type' => 'options',
                'options' => [
                1 => 'Abandoned Cart Exclusion Rule',
                2 => 'Review Email Exclusion Rule',
                ],
                ]
            );
            $this->addColumn(
                'status',
                [
                'header' => __('Status'),
                'align' => 'left',
                'width' => '80px',
                'index' => 'status',
                'type' => 'options',
                'options' => [
                1 => 'Active',
                0 => 'Inactive',
                ],
                ]
            );

            $this->addColumn(
                'created_at',
                [
                'header' => __('Created At'),
                'align' => 'left',
                'width' => '120px',
                'type' => 'datetime',
                'index' => 'created_at',
                ]
            );

            $this->addColumn(
                'updated_at',
                [
                'header' => __('Updated At'),
                'align' => 'left',
                'width' => '120px',
                'type' => 'datetime',
                'index' => 'updated_at',
                ]
            );

            return parent::_prepareColumns();
    }

    /**
     * @return $this
     */
    public function _prepareMassaction()
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

        return $this;
    }

    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\DataObject $row
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            'dotdigitalgroup_email/*/edit',
            ['id' => $row->getId()]
        );
    }
}
