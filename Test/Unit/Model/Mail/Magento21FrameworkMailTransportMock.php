<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Mail;

/**
 * Used to mock _message reflection
 */
class Magento21FrameworkMailTransportMock
{
    /**
     * @var \Magento\Framework\Mail\MessageInterface
     */
    // phpcs:disable
    private $_message;
    // phpcs:enable

    public function setMessage($message)
    {
        $this->_message = $message;
    }

    /**
     * Send a mail using this transport
     *
     * @return void
     */
    public function sendMessage()
    {
    }

    /**
     * Get message
     *
     * @return \Magento\Framework\Mail\MessageInterface
     * @since 100.2.0
     */
    public function getMessage()
    {
        return $this->_message;
    }
}
