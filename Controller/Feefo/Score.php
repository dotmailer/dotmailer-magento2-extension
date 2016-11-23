<?php

namespace Dotdigitalgroup\Email\Controller\Feefo;

class Score extends \Dotdigitalgroup\Email\Controller\Response
{
    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        //authenticate
        $this->authenticate();

        if (!$this->helper->getFeefoLogon()) {
            $this->sendResponse();

            return;
        }

        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
