<?php

namespace Dotdigitalgroup\Email\Block\Adminhtml\Order;

use Magento\Backend\Block\Widget\Grid as WidgetGrid;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;


    protected $_gridFactory;
    protected $_orderFactory;
    protected $_configFactory;
    protected $_importedFactory;

    /**
     * Grid constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory $importedFactory
     * @param \Magento\Sales\Model\Order\ConfigFactory                              $configFactory
     * @param \Magento\Backend\Block\Template\Context                               $context
     * @param \Magento\Backend\Helper\Data                                          $backendHelper
     * @param \Dotdigitalgroup\Email\Model\Resource\Order\CollectionFactory         $gridFactory
     * @param \Magento\Framework\Module\Manager                                     $moduleManager
     * @param array                                                                 $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\Adminhtml\Source\Contact\ImportedFactory $importedFactory,
        \Magento\Sales\Model\Order\ConfigFactory $configFactory,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Dotdigitalgroup\Email\Model\Resource\Order\CollectionFactory $gridFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    ) {
        $this->_importedFactory = $importedFactory;
        $this->_configFactory   = $configFactory;
        $this->_orderFactory    = $gridFactory;
        $this->moduleManager    = $moduleManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
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
            'email_order_id', array(
                'header' => __('Order ID'),
                'align'  => 'left',
                'width'  => '50px',
                'index'  => 'email_order_id',
                'type'   => 'number',
                'escape' => true
            )
        )->addColumn(
            'store_id', array(
                'header' => __('Store ID'),
                'width'  => '50px',
                'index'  => 'store_id',
                'type'   => 'number',
                'escape' => true,
            )
        )->addColumn(
            'order_status', array(
                'header'  => __('Order Status'),
                'align'   => 'right',
                'width'   => '50px',
                'index'   => 'order_status',
                'type'    => 'options',
                'escape'  => true,
                'options' => $this->_configFactory->create()->getStatuses(),
            )
        )->addColumn(
            'email_imported', array(
                'header'                    => __('Imported'),
                'align'                     => 'center',
                'width'                     => '50px',
                'index'                     => 'email_imported',
                'type'                      => 'options',
                'escape'                    => true,
                'renderer'                  => 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
                'options'                   => $this->_importedFactory->create()
                    ->getOptions(),
                'filter_condition_callback' => array(
                    $this,
                    'filterCallbackContact'
                )
            )
        )->addColumn(
            'modified', array(
                'header'                    => __('Modified'),
                'align'                     => 'center',
                'width'                     => '50px',
                'index'                     => 'modified',
                'type'                      => 'options',
                'escape'                    => true,
                'renderer'                  => 'Dotdigitalgroup\Email\Block\Adminhtml\Column\Renderer\Imported',
                'options'                   => [
                    1    => 'Modified',
                    null => 'Not Modified',
                ],
                'filter_condition_callback' => array(
                    $this,
                    'filterCallbackContact'
                )
            )
        )->addColumn(
            'created_at', array(
                'header' => __('Created At'),
                'width'  => '50px',
                'align'  => 'center',
                'index'  => 'created_at',
                'type'   => 'datetime',
                'escape' => true,
            )
        )->addColumn(
            'updated_at', array(
                'header' => __('Updated At'),
                'width'  => '50px',
                'align'  => 'center',
                'index'  => 'updated_at',
                'type'   => 'datetime',
                'escape' => true,
            )
        );

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
        $this->setMassactionIdField('email_order_id');
        $this->getMassactionBlock()->setFormFieldName('email_order_id');

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label'   => __('Delete'),
                'url'     => $this->getUrl('*/*/massDelete'),
                'confirm' => __('Are you sure?')
            ]
        );

        return $this;
    }

}
