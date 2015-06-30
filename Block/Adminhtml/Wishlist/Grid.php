<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Wishlist_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
        return 'ddg_automation/wishlist_collection';
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
        $this->addColumn('wishlist_id', array(
            'header'        => Mage::helper('ddg')->__('Wishlist ID'),
            'align'         => 'left',
            'width'         => '50px',
            'index'         => 'wishlist_id',
            'type'          => 'number',
            'escape'        => true
        ))->addColumn('customer_id', array(
	        'header'        => Mage::helper('ddg')->__('Customer ID'),
	        'align'         => 'left',
	        'width'         => '50px',
	        'index'         => 'customer_id',
	        'type'          => 'number',
	        'escape'        => true
        ))->addColumn('item_count', array(
            'header'        => Mage::helper('ddg')->__('Item Count'),
            'align'         => 'left',
            'width'         => '50px',
            'index'         => 'item_count',
            'type'          => 'number',
            'escape'        => true
        ))->addColumn('wishlist_imported', array(
            'header'        => Mage::helper('ddg')->__('Wishlist Imported'),
            'align'         => 'center',
            'width'         => '50px',
            'index'         => 'wishlist_imported',
            'type'          => 'options',
            'escape'        => true,
            'renderer'		=> 'ddg_automation/adminhtml_column_renderer_imported',
            'options'       => Mage::getModel('ddg_automation/adminhtml_source_contact_imported')->getOptions(),
            'filter_condition_callback' => array($this, 'filterCallbackContact')
        ))->addColumn('wishlist_modified', array(
            'header'        => Mage::helper('ddg')->__('Wishlist Modified'),
            'align'         => 'center',
            'width'         => '50px',
            'index'         => 'wishlist_modified',
            'type'          => 'options',
            'escape'        => true,
            'renderer'		=> 'ddg_automation/adminhtml_column_renderer_imported',
            'options'       => Mage::getModel('ddg_automation/adminhtml_source_contact_imported')->getOptions(),
            'filter_condition_callback' => array($this, 'filterCallbackContact')
        ))->addColumn('created_at', array(
            'header'        => Mage::helper('ddg')->__('Created At'),
            'width'         => '50px',
            'align'         => 'center',
            'index'         => 'created_at',
            'type'          => 'datetime',
            'escape'        => true,
        ))->addColumn('updated_at', array(
            'header'        => Mage::helper('ddg')->__('Updated At'),
            'width'         => '50px',
            'align'         => 'center',
            'index'         => 'updated_at',
            'type'          => 'datetime',
            'escape'        => true,
        ));

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
        $field = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
        $value = $column->getFilter()->getValue();
        if ($value == 'null') {
            $collection->addFieldToFilter($field, array('null' => true));
        } else {
            $collection->addFieldToFilter($field, array('notnull' => true));
        }
    }
}