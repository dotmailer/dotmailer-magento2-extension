<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Order;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var
     */
    protected $_gridFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderFactory;
    /**
     * @var \Magento\Sales\Model\Order\ConfigFactory
     */
    protected $_configFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory
     */
    protected $_importedFactory;

    /**
     * Grid constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory $importedFactory
     * @param \Magento\Sales\Model\Order\ConfigFactory                              $configFactory
     * @param \Magento\Backend\Block\Template\Context                               $context
     * @param \Magento\Backend\Helper\Data                                          $backendHelper
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $gridFactory
     * @param \Magento\Framework\Module\Manager                                     $moduleManager
     * @param array                                                                 $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory $importedFactory,
        \Magento\Sales\Model\Order\ConfigFactory $configFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Dotdigitalgroup\Email\Model\ResourceModel\Order\CollectionFactory $gridFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_importedFactory = $importedFactory;
        $this->_configFactory = $configFactory;
        $this->_orderFactory = $gridFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Constructor.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('order');
        $this->setDefaultSort('email_order_id');
        $this->setDefaultDir('DESC');
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_orderFactory->create();
        $this->setCollection($collection);

        parent::_prepareCollection();

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'email_order_id', [
                'header' => __('Order ID'),
            'align' => 'left',
            'index' => 'email_order_id',
            'type' => 'number',
            'escape' => true,
        ])->addColumn(
            'store_id', [
                'header' => __('Store ID'),
            'index' => 'store_id',
            'type' => 'number',
                'escape' => true,
        ])->addColumn(
            'order_status', [
            'header' => __('Order Status'),
            'align' => 'right',
            'index' => 'order_status',
            'type' => 'options',
            'escape' => true,
                'options' => $this->_configFactory->create()->getStatuses(),
        ])->addColumn(
            'email_imported', [
            'header' => __('Imported'),
            'align' => 'center',
            'index' => 'email_imported',
            'type' => 'options',
            'escape' => true,
            'renderer' => 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
            'options' => $this->_importedFactory->create()
                    ->getOptions(),
            'filter_condition_callback' => [
                    $this,
                'filterCallbackContact',
            ],
        ])->addColumn(
            'modified', [
            'header' => __('Modified'),
            'align' => 'center',
            'index' => 'modified',
            'type' => 'options',
            'escape' => true,
            'renderer' => 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
            'options' => [
                1 => 'Modified',
                    null => 'Not Modified',
                ],
            'filter_condition_callback' => [
                    $this,
                'filterCallbackContact',
            ],
        ])->addColumn(
            'created_at', [
                'header' => __('Created At'),
            'align' => 'center',
            'index' => 'created_at',
            'type' => 'datetime',
                'escape' => true,
        ])->addColumn(
            'updated_at', [
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
        $this->setMassactionIdField('email_order_id');
        $this->getMassactionBlock()->setFormFieldName('email_order_id');

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
