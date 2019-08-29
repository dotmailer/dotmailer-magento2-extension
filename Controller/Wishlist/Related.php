<?php

namespace Dotdigitalgroup\Email\Controller\Wishlist;

class Related extends \Dotdigitalgroup\Email\Controller\Edc
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
            $this->checkResponse();
        }
    }
}
