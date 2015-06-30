<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Automation_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('id');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);

    }

    protected function _prepareCollection()
    {
	    $collection = Mage::getModel('ddg_automation/automation')->getCollection();
        $this->setCollection($collection);
        $this->setDefaultSort('updated_at');
        $this->setDefaultDir('DESC');
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id', array(
            'header'        => Mage::helper('ddg')->__('ID'),
            'width'         => '20px',
            'index'         => 'id',
            'type'          => 'number',
            'escape'        => true,
        ))->addColumn('program_id', array(
	        'header'        => Mage::helper('ddg')->__('Program ID'),
	        'align'         => 'center',
	        'width'         => '50px',
	        'index'         => 'program_id',
	        'type'          => 'number',
	        'escape'        => true,
        ))->addColumn('automation_type', array(
            'header'        => Mage::helper('ddg')->__('Automation Type'),
            'align'         => 'right',
            'width'         => '50px',
            'index'         => 'automation_type',
            'type'          => 'text',
            'escape'        => true
        ))->addColumn('enrolment_status', array(
            'header'        => Mage::helper('ddg')->__('Enrollment Status'),
            'align'         => 'left',
            'width'         => '20px',
            'index'         => 'enrolment_status',
            'type'          => 'text',
            'escape'        => true
        ))->addColumn('email', array(
            'header'        => Mage::helper('ddg')->__('Email'),
            'width'         => '50px',
            'align'         => 'right',
            'index'         => 'email',
            'type'          => 'text',
            'escape'        => true,
        ))->addColumn('type_id', array(
            'header'        => Mage::helper('ddg')->__('Type ID'),
            'align'         => 'center',
            'width'         => '50px',
            'index'         => 'type_id',
            'type'          => 'number',
            'escape'        => true,
        ))->addColumn('message', array(
	        'header'        => Mage::helper('ddg')->__('Message'),
	        'width'         => '50px',
	        'align'         => 'right',
	        'index'         => 'message',
	        'type'          => 'text',
	        'escape'        => true
        ))->addColumn('created_at', array(
            'header'        => Mage::helper('ddg')->__('Created_at'),
            'width'         => '20px',
            'align'         => 'center',
            'index'         => 'created_at',
            'escape'        => true,
            'type'          => 'date'

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

        $this->addExportType('*/*/exportCsv', Mage::helper('ddg')->__('CSV'));
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
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    /**
	 * Prepare the grid massaction.
	 * @return $this|Mage_Adminhtml_Block_Widget_Grid
	 */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('automation');
	    $this->getMassactionBlock()->addItem('resend', array(
		    'label' => Mage::helper('ddg')->__('Resend'),
		    'url' => $this->getUrl('*/*/massResend'),

	    ));
	    $this->getMassactionBlock()->addItem('delete', array(
		    'label'=> Mage::helper('ddg')->__('Delete'),
		    'url'  => $this->getUrl('*/*/massDelete'),
		    'confirm'  => Mage::helper('ddg')->__('Are you sure?')));

        return $this;
    }

    /**
	 * Edit the row.
	 * @param $row
	 *
	 * @return string
	 */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getEmailContactId()));
    }

    /**
	 * Grid url.
	 * @return string
	 */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

}