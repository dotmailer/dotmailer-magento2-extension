<?php

namespace Dotdigitalgroup\Email\Controller\Feefo;

use Dotdigitalgroup\Email\Controller\ExternalDynamicContentController;

class Reviews extends ExternalDynamicContentController
{
    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->authenticate()) {
            return $this->response;
        }

        $this->layout = $this->resultLayoutFactory->create();
        if (!$this->helper->getFeefoLogon()) {
            $this->setNoContentResponse();
            return $this->layout;
        }

        $this->checkResponse();
        return $this->layout;
    }
}
