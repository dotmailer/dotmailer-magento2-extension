<?php

namespace Dotdigitalgroup\Email\Controller\Feefo;

class Reviews extends \Dotdigitalgroup\Email\Controller\Response
{
    public function execute()
    {
        //authenticate
        $this->authenticate();

        if (!$this->_helper->getFeefoLogon() or !$this->getRequest()->getParam('quote_id')){
            $this->sendResponse();
            return;
        }

        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}