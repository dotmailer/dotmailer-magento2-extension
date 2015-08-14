<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Rules_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();

        // Set some defaults for our grid
        $this->setDefaultSort('id');
        $this->setId('ddg_rules_grid');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare the grid collection.
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('ddg_automation/rules')->getResourceCollection();
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }

    /**
     * Add columns to grid
     *
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('rule_id', array(
            'header'    => Mage::helper('ddg')->__('ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'id',
        ));

        $this->addColumn('name', array(
            'header'    => Mage::helper('ddg')->__('Rule Name'),
            'align'     =>'left',
            'width'     => '150px',
            'index'     => 'name',
        ));

        $this->addColumn('type', array(
            'header'    => Mage::helper('ddg')->__('Rule Type'),
            'align'     => 'left',
            'width'     => '150px',
            'index'     => 'type',
            'type'      => 'options',
            'options'   => array(
                1 => 'Abandoned Cart Exclusion Rule',
                2 => 'Review Email Exclusion Rule',
            ),
        ));

        $this->addColumn('status', array(
            'header'    => Mage::helper('ddg')->__('Status'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'status',
            'type'      => 'options',
            'options'   => array(
                1 => 'Active',
                0 => 'Inactive',
            ),
        ));

	    $this->addColumn('created_at', array(
		    'header'    => Mage::helper('ddg')->__('Created At'),
		    'align'     => 'left',
		    'width'     => '120px',
		    'type'      => 'datetime',
		    'index'     => 'created_at',
	    ));

	    $this->addColumn('updated_at', array(
		    'header'    => Mage::helper('ddg')->__('Updated At'),
		    'align'     => 'left',
		    'width'     => '120px',
		    'type'      => 'datetime',
		    'index'     => 'updated_at',
	    ));

	    if (!Mage::app()->isSingleStoreMode()) {
		    $this->addColumn('rule_website', array(
			    'header'    => Mage::helper('salesrule')->__('Website'),
			    'align'     =>'left',
			    'index'     => 'website_ids',
			    'type'      => 'options',
			    'sortable'  => false,
			    'options'   => Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash(),
			    'width'     => 150,
		    ));
	    }
	    parent::_prepareColumns();
        return $this;
    }

    /**
     * Retrieve row click URL
     *
     * @param Varien_Object $row
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}