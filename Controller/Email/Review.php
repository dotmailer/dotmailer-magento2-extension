<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Review extends \Dotdigitalgroup\Email\Controller\Response
{
    /**
     * Review page to display the user items with specific email.
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
