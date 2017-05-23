<?php

namespace Dotdigitalgroup\Email\Controller\Quoteproducts;

/**
 * Class Upsell
 * @package Dotdigitalgroup\Email\Controller\Quoteproducts
 */
class Upsell extends \Dotdigitalgroup\Email\Controller\Response
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
