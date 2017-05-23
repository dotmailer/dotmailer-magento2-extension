<?php

namespace Dotdigitalgroup\Email\Controller\Quoteproducts;

/**
 * Class Crosssell
 * @package Dotdigitalgroup\Email\Controller\Quoteproducts
 */
class Crosssell extends \Dotdigitalgroup\Email\Controller\Response
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
    }
}
