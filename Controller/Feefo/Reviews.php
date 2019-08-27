<?php

namespace Dotdigitalgroup\Email\Controller\Feefo;

class Reviews extends \Dotdigitalgroup\Email\Controller\Edc
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
            $quote = $this->getRequest()->getParam('quote_id');
            if (!$this->helper->getFeefoLogon() or !$quote) {
                return $this->sendNoContentResponse();
            }

            $this->_view->loadLayout();
            $this->_view->renderLayout();
        }
    }
}
