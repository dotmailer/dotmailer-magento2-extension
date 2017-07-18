<?php

namespace Dotdigitalgroup\Email\Controller\Wishlist;

class Crosssell extends \Dotdigitalgroup\Email\Controller\Response
{
    /**
     * Basket page to display the user items with specific email.
     *
     * @return null
     */
    public function execute()
    {
        //authenticate
        if ($this->authenticate()) {
            $this->_view->loadLayout();
            $this->_view->renderLayout();
        }
    }
}
