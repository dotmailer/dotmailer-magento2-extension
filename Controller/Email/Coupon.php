<?php

namespace Dotdigitalgroup\Email\Controller\Email;

class Coupon extends \Dotdigitalgroup\Email\Controller\Response
{
    /**
     * @return void
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
