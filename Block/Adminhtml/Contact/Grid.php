<?php

class Dotdigitalgroup_Email_Block_Adminhtml_Contact_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('email_contact_id');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
	    $collection = Mage::getModel('ddg_automation/contact')->getCollection();
        $this->setCollection($collection);
        $this->setDefaultSort('updated_at');
        $this->setDefaultDir('DESC');
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('email_contact_id', array(
            'header'        => Mage::helper('ddg')->__('Email Contact ID'),
            'width'         => '20px',
            'index'         => 'email_contact_id',
            'type'          => 'number',
            'escape'        => true,
        ))->addColumn('email', array(
            'header'        => Mage::helper('ddg')->__('Email'),
            'align'         => 'left',
            'width'         => '50px',
            'index'         => 'email',
            'type'          => 'text',
            'escape'        => true
        ))->addColumn('customer_id', array(
	        'header'        => Mage::helper('ddg')->__('Customer ID'),
	        'align'         => 'left',
	        'width'         => '20px',
	        'index'         => 'customer_id',
	        'type'          => 'number',
	        'escape'        => true
        ))->addColumn('is_guest', array(
            'header'        => Mage::helper('ddg')->__('Is Guest'),
            'align'         => 'right',
            'width'         => '50px',
            'index'         => 'is_guest',
            'type'          => 'options',
	        'options'       => array(
		        '1'    => 'Guest',
		        'null' => 'Not Guest'
	        ),
            'escape'        => true,
            'filter_condition_callback' => array($this, 'filterCallbackContact')
        ))->addColumn('is_subscriber', array(
            'header'        => Mage::helper('ddg')->__('Is Subscriber'),
            'width'         => '50px',
            'align'         => 'right',
            'index'         => 'is_subscriber',
            'type'          => 'options',
	        'options'   => array(
		        '1'  => 'Subscriber',
		        'null' => 'Not Subscriber'
	        ),
	        'filter_condition_callback' => array($this, 'filterCallbackContact'),
            'escape'        => true,
        ))->addColumn('subscriber_status', array(
            'header'        => Mage::helper('ddg')->__('Subscriber Status'),
            'align'         => 'center',
            'width'         => '50px',
            'index'         => 'subscriber_status',
            'type'          => 'options',
	        'options'       => array(
		        '1' => 'Subscribed',
		        '2' => 'Not Active',
		        '3' => 'Unsubscribed',
		        '4' => 'Unconfirmed'
	        ),
            'escape'        => true,
        ))->addColumn('email_imported', array(
            'header'        => Mage::helper('ddg')->__('Email Imported'),
            'width'         => '20px',
            'align'         => 'center',
            'index'         => 'email_imported',
            'escape'        => true,
            'type'          => 'options',
            'options'       => Mage::getModel('ddg_automation/adminhtml_source_contact_imported')->getOptions(),
            'renderer'      => 'ddg_automation/adminhtml_column_renderer_imported',
            'filter_condition_callback' => array($this, 'filterCallbackContact')
        ))->addColumn('subscriber_imported', array(
            'header'        => Mage::helper('ddg')->__('Subscriber Imported'),
            'width'         => '20px',
            'align'         => 'center',
            'index'         => 'subscriber_imported',
            'type'          => 'options',
            'escape'        => true,
            'renderer'      => 'ddg_automation/adminhtml_column_renderer_imported',
            'options'       => Mage::getModel('ddg_automation/adminhtml_source_contact_imported')->getOptions(),
            'filter_condition_callback' => array($this, 'filterCallbackContact')
        ))->addColumn('suppressed', array(
            'header'        => Mage::helper('ddg')->__('Suppressed'),
            'align'         => 'right',
            'width'         => '50px',
            'index'         => 'suppressed',
            'escape'        => true,
            'type'          => 'options',
	        'options'       => array(
		        '1'     => 'Suppressed',
		        'null'  => 'Not Suppressed'
	        ),
            'filter_condition_callback' => array($this, 'filterCallbackContact')
        ));

	    //Enterprise customer segments.
	    if (Mage::helper('ddg')->isEnterprise()) {
		    $this->addColumn( 'segment_ids', array(
			    'header' => Mage::helper( 'ddg' )->__( 'Segment Id\'s' ),
			    'align'  => 'right',
			    'width'  => '50px',
			    'index'  => 'segment_ids',
			    'escape' => true,
			    'type'   => 'text'
		    ) );
	    }
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

        $this->addColumn('sync', array(
            'header' => Mage::helper('ddg')->__('Sync Contact'),
            'align'         => 'center',
            'width'         => '80px',
            'renderer'     => 'ddg_automation/adminhtml_column_renderer_sync'

        ));

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
        $this->setMassactionIdField('email_contact_id');
        $this->getMassactionBlock()->setFormFieldName('contact');
        $this->getMassactionBlock()->addItem('delete', array(
            'label'=> Mage::helper('ddg')->__('Delete'),
            'url'  => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('ddg')->__('Are you sure?')));
        $this->getMassactionBlock()->addItem('resend', array(
            'label' => Mage::helper('ddg')->__('Resend'),
            'url' => $this->getUrl('*/*/massResend'),

        ));
        return $this;
    }

    /**
	 * Custom callback action for the subscribers/contacts.
	 * @param $collection
	 * @param $column
	 */
    public function filterCallbackContact($collection, $column)
    {
        $field = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();
        $value = $column->getFilter()->getValue();

        if ($value == 'null')
              $collection->addFieldToFilter($field, array('null' => true));
        else
            $collection->addFieldToFilter($field, array('notnull' => true));
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