<?php

namespace Dotdigitalgroup\Email\Controller\Report;

class Mostviewed extends \Dotdigitalgroup\Email\Controller\Response
{

	public function execute()
	{
		//authenticate
		$this->authenticate();
		$this->_view->loadLayout();
		$this->_view->renderLayout();
		//$this->checkContentNotEmpty( $this->_view->getLayout()->getOutput() );
	}

	/**
	 * Recently viewed products for customer.
	 */
	public function recentlyviewedAction()
    {
	    //customer id param
        $customerId = $this->getRequest()->getParam('customer_id');
	    //no customer was found
        if (! $customerId) {
            //throw new Exception('Recentlyviewed : no customer id : ' . $customerId);
            Mage::helper('ddg')->log('Recentlyviewed : no customer id : ' . $customerId);
            $this->sendResponse();
            die;
        }
        $this->loadLayout();
	    //set content template
        $products = $this->getLayout()->createBlock('ddg_automation/recommended_recentlyviewed', 'connector_customer', array(
            'template' => 'connector/product/list.phtml'
        ));
        $this->getLayout()->getBlock('content')->append($products);
        $this->renderLayout();
        $this->checkContentNotEmpty($this->getLayout()->getOutput());
    }
}