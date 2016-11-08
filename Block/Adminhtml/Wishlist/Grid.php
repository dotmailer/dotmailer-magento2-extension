<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Wishlist;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory
     */
    public $wishlistFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory
     */
    public $importedFactory;

    /**
     * Grid constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory $importedFactory
     * @param \Magento\Backend\Block\Template\Context                               $context
     * @param \Magento\Backend\Helper\Data                                          $backendHelper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $gridFactory
     * @param array                                                                 $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory $importedFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Wishlist\CollectionFactory $gridFactory,
        array $data = []
    ) {
        $this->importedFactory = $importedFactory;
        $this->wishlistFactory = $gridFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Constructor.
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('wishlist');
        $this->setDefaultSort('wishlist_id');
        $this->setDefaultDir('DESC');
    }

    /**
     * @return $this
     */
    public function _prepareCollection()
    {
        $this->setCollection($this->wishlistFactory->create());

        return parent::_prepareCollection();
    }

    /**
     * @return $this
     *
     * @throws \Exception
     */
    public function _prepareColumns()
    {
        $this->addColumn('wishlist_id', [
            'header' => __('Wishlist ID'),
            'align' => 'left',
            'index' => 'wishlist_id',
            'type' => 'number',
            'escape' => true,
        ])->addColumn('customer_id', [
            'header' => __('Customer ID'),
            'align' => 'left',
            'index' => 'customer_id',
            'type' => 'number',
            'escape' => true,
        ])->addColumn('item_count', [
            'header' => __('Item Count'),
            'align' => 'left',
            'index' => 'item_count',
            'type' => 'number',
            'escape' => true,
        ])->addColumn('wishlist_imported', [
            'header' => __('Wishlist Imported'),
            'align' => 'center',
            'index' => 'wishlist_imported',
            'type' => 'options',
            'escape' => true,
            'renderer' => 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
            'options' => $this->importedFactory->create()
                ->getOptions(),
            'filter_condition_callback' => [$this, 'filterCallbackContact'],
        ])->addColumn('wishlist_modified', [
            'header' => __('Wishlist Modified'),
            'align' => 'center',
            'index' => 'wishlist_modified',
            'type' => 'options',
            'escape' => true,
            'renderer' => 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
            'options' => $this->importedFactory->create()
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
}
