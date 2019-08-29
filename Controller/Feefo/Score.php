<?php

namespace Dotdigitalgroup\Email\Controller\Feefo;

class Score extends \Dotdigitalgroup\Email\Controller\Edc
{
    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return null
     */
    public function execute()
    {
        //authenticate
        if ($this->authenticate()) {
            if ($this->helper->getFeefoLogon()) {
                $this->_view->loadLayout();
                $this->_view->renderLayout();
            }
        }
    }
}
