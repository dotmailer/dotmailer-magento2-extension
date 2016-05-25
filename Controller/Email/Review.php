<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Review extends \Dotdigitalgroup\Email\Controller\Response
{
    /**
     * Review page to display the user items with specific email.
     */
    public function execute()
    {
        //authenticate
        $this->authenticate();
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
