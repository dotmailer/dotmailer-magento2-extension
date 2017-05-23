<?php

namespace Dotdigitalgroup\Email\Controller\Wishlist;

/**
 * Class Related
 * @package Dotdigitalgroup\Email\Controller\Wishlist
 */
class Related extends \Dotdigitalgroup\Email\Controller\Response
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
