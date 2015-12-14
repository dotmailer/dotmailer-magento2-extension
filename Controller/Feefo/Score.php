<?php

namespace Dotdigitalgroup\Email\Controller\Feefo;

class Score extends \Dotdigitalgroup\Email\Controller\Response
{
    public function execute()
    {
        //authenticate
        $this->authenticate();

        if (!$this->_helper->getFeefoLogon()){
            $this->sendResponse();
            exit;
        }

        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}