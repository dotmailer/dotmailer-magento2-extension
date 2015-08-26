<?php

namespace Dotdigitalgroup\Email\Controller\Email;

use GuzzleHttp;
use \Dotdigitalgroup\Email\Model\Cron;

class Basket extends \Dotdigitalgroup\Email\Controller\Response
{

	/**
	 * Basket page to display the user items with specific email.
	 */
	public function execute()
	{
		//authenticate
		$this->authenticate();
		$this->_view->loadLayout();

//		if ( $root = $this->_view->getLayout()->getBlock( 'root' ) ) {
//			$root->setTemplate( 'page/blank.phtml' );
//		}
//		$basket = $this->_view->getLayout()->createBlock( 'Dotdigitalgroup\Email\Block\Basket', 'connector_basket', array(
//			'template' => 'connector/basket.phtml'
//		) );


		//$this->_view->getLayout()->getBlock( 'content' )->( $basket );

		$this->_view->renderLayout();
///		$this->checkContentNotEmpty( $this->_view->getLayout()->getOutput() );
	}
}