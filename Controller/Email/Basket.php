<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Basket extends \Dotdigitalgroup\Email\Controller\Response
{
    /**
     * Abandoned cart page.
     */
    public function execute()
    {
        //authenticate
        $this->authenticate();
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
