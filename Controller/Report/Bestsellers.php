<?php

namespace Dotdigitalgroup\Email\Controller\Report;

class Bestsellers extends \Dotdigitalgroup\Email\Controller\Response
{
    /**
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
