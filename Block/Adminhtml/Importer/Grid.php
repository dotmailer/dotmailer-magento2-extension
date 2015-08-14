<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Importer_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        // Set some defaults for our grid
        $this->setDefaultSort('id');
        $this->setId('id');
        $this->setDefaultDir('asc');
    }

    /**
     * Collection class;
     * @return string
     */
    protected function _getCollectionClass()
    {
        // This is the model we are using for the grid
        return 'ddg_automation/importer_collection';
    }

    /**
     * Prepare the grid collection.
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        // Get and set our collection for the grid
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare the grid collumns.
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header' => Mage::helper('ddg')->__('ID'),
            'align' => 'left',
            'index' => 'id',
            'type' => 'number',
            'escape' => true
        ))->addColumn('import_type', array(
            'header' => Mage::helper('ddg')->__('Import Type'),
            'width' => '50px',
            'index' => 'import_type',
            'type' => 'text',
            'escape' => true
        ))->addColumn('import_status', array(
            'header' => Mage::helper('ddg')->__('Import Status'),
            'width' => '50px',
            'index' => 'import_status',
            'type' => 'options',
            'escape' => true,
            'options' => Mage::getModel('ddg_automation/adminhtml_source_importer_status')->getOptions(),
        ))->addColumn('message', array(
            'header' => Mage::helper('ddg')->__('Error Message'),
            'index' => 'message',
            'type' => 'text',
            'escape' => true
        ))->addColumn('import_mode', array(
            'header' => Mage::helper('ddg')->__('Import Mode'),
            'width' => '50px',
            'index' => 'import_mode',
            'type' => 'options',
            'escape' => true,
            'options' => Mage::getModel('ddg_automation/adminhtml_source_importer_mode')->getOptions(),
        ))->addColumn('import_id', array(
            'header' => Mage::helper('ddg')->__('Import ID'),
            'width' => '50px',
            'index' => 'import_id',
            'type' => 'text',
            'escape' => true
        ))->addColumn('import_started', array(
            'header' => Mage::helper('ddg')->__('Imported Started At'),
            'width' => '50px',
            'align' => 'center',
            'index' => 'import_started',
            'type' => 'datetime',
            'escape' => true
        ))->addColumn('import_finished', array(
            'header' => Mage::helper('ddg')->__('Last Import Check Time'),
            'width' => '50px',
            'align' => 'center',
            'index' => 'import_finished',
            'type' => 'datetime',
            'escape' => true
        ))->addColumn('script', array(
            'header' => Mage::helper('ddg')->__('Script'),
            'renderer' => 'ddg_automation/adminhtml_column_renderer_script',
            'column_css_class' => 'no-display',
            'header_css_class' => 'no-display'
        ))->addColumn('created_at', array(
	        'header' => Mage::helper('ddg')->__('Created At'),
	        'width' => '50px',
	        'align' => 'center',
	        'index' => 'created_at',
	        'type' => 'datetime',
	        'escape' => true
        ))->addColumn('updated_at', array(
	        'header' => Mage::helper('ddg')->__('Updated At'),
	        'width' => '50px',
	        'align' => 'center',
	        'index' => 'updated_at',
	        'type' => 'datetime',
	        'escape' => true

        ));
	    if (!Mage::app()->isSingleStoreMode()) {
		    $this->addColumn('website_id', array(
			    'header'    => Mage::helper('customer')->__('Website'),
			    'align'     => 'center',
			    'width'     => '80px',
			    'type'      => 'options',
			    'options'   => Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash(true),
			    'index'     => 'website_id',
		    ));
	    }


	    return parent::_prepareColumns();
    }

    /**
     * Get the store.
     *
     * @return Mage_Core_Model_Store
     * @throws Exception
     */
    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

	/**
	 * Prepare the grid massaction.
	 * @return $this|Mage_Adminhtml_Block_Widget_Grid
	 */
	protected function _prepareMassaction()
	{
		$this->setMassactionIdField('id');
		$this->getMassactionBlock()->setFormFieldName('importer');
		$this->getMassactionBlock()->addItem('resend', array(
			'label' => Mage::helper('ddg')->__('Reset'),
			'url' => $this->getUrl('*/*/massResend'),

		));
		$this->getMassactionBlock()->addItem('delete', array(
			'label'=> Mage::helper('ddg')->__('Delete'),
			'url'  => $this->getUrl('*/*/massDelete'),
			'confirm'  => Mage::helper('ddg')->__('Are you sure?')));

		return $this;
	}
}