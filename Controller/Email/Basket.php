<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Basket extends \Dotdigitalgroup\Email\Controller\Response
{
    /**
     * Abandoned cart page.
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
