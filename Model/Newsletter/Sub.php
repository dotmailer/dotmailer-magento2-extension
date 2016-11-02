<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

class Sub extends \Magento\Newsletter\Model\Subscriber
{
    /**
     * Sends out confirmation success email.
     *
     * @return $this
     */
    public function sendConfirmationSuccessEmail()
    {
        if ($this->_scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DISABLE_NEWSLETTER_SUCCESS,
            'store',
            $this->getStoreId()
        )
        ) {
            return $this;
        } else {
            return parent::sendConfirmationSuccessEmail();
        }
    }
}
