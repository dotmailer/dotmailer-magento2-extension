<?php

namespace Dotdigitalgroup\Email\Controller\Report;

class Recentlyviewed extends \Dotdigitalgroup\Email\Controller\Response
{
    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        //authenticate
        $this->authenticate();
        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
