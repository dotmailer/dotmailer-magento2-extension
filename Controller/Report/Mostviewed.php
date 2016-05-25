<?php

namespace Dotdigitalgroup\Email\Controller\Report;

class Mostviewed extends \Dotdigitalgroup\Email\Controller\Response
{
    public function execute()
    {
        //authenticate
        $this->authenticate();
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
