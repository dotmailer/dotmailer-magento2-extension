<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Review;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var
     */
    protected $_gridFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Review\CollectionFactory
     */
    protected $_reviewFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory
     */
    protected $_importedFactory;

    /**
     * Grid constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory $importedFactory
     * @param \Magento\Backend\Block\Template\Context                               $context
     * @param \Magento\Backend\Helper\Data                                          $backendHelper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Review\CollectionFactory   $gridFactory
     * @param array                                                                 $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory $importedFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Review\CollectionFactory $gridFactory,
        array $data = []
    ) {
        $this->_importedFactory = $importedFactory;
        $this->_reviewFactory = $gridFactory;

        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Constructor.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('review');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $this->setCollection($this->_reviewFactory->create());

        return parent::_prepareCollection();
    }

    /**
     * @return $this
     *
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('review_id', [
            'header' => __('Review ID'),
            'align' => 'left',
            'index' => 'review_id',
            'type' => 'number',
            'escape' => true,
        ])->addColumn('customer_id', [
            'header' => __('Customer ID'),
            'align' => 'left',
            'index' => 'customer_id',
            'type' => 'number',
            'escape' => true,
        ])->addColumn('store_id', [
            'header' => __('Store ID'),
            'index' => 'store_id',
            'type' => 'number',
            'escape' => true,
        ])->addColumn('review_imported', [
            'header' => __('Review Imported'),
            'align' => 'center',
            'index' => 'review_imported',
            'type' => 'options',
            'escape' => true,
            'renderer' => 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
            'options' => $this->_importedFactory->create()
                ->getOptions(),
            'filter_condition_callback' => [$this, 'filterCallbackContact'],
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
