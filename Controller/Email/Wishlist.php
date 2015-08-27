<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Wishlist extends \Dotdigitalgroup\Email\Controller\Response
{

	/**
	 * Basket page to display the user items with specific email.
	 */
	public function execute()
	{
		//authenticate
		$this->authenticate();
		$this->_view->loadLayout();
		$this->_view->renderLayout();
		//$this->checkContentNotEmpty( $this->_view->getLayout()->getOutput() );
	}
}