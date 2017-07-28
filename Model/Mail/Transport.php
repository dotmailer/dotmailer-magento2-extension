<?php

namespace Dotdigitalgroup\Email\Model\Mail;

class Transport implements \Dotdigitalgroup\Email\Model\Mail\AdapterInterface
{
    /**
     * @var \Zend\Mail\Transport\Smtp
     */
    protected $smtp;

    /**
     * @param \Zend\Mail\Transport\Smtp $smtp
     */
    public function __construct($host, $config) {
        $this->smtp = new \Zend_Mail_Transport_Smtp($host, $config);
    }

    /**
     * Send message
     *
     * @param \Magento\Framework\Mail\MessageInterface $message
     * @return void
     */
    public function send(\Magento\Framework\Mail\MessageInterface $message)
    {
        $this->smtp->send($message);
    }
}