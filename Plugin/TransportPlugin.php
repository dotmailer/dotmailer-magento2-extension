<?php

namespace Dotdigitalgroup\Email\Plugin;

use Magento\Framework\Mail\TransportInterface;

/**
 * SMTP mail transport.
 */
class TransportPlugin
{
    /**
     * @var \Magento\Framework\Mail\MessageInterface
     */
    private $message;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Transactional
     */
    private $helper;

    /**
     * @var \Zend_Mail_Transport_Smtp
     */
    private $smtp;

    /**
     * TransportPlugin constructor.
     *
     * @param \Magento\Framework\Mail\MessageInterface $message
     * @param \Zend_Mail_Transport_SmtpFactory $smtpFactory
     * @param \Dotdigitalgroup\Email\Helper\Transactional $helper
     */
    public function __construct(
        \Magento\Framework\Mail\MessageInterface $message,
        \Zend_Mail_Transport_SmtpFactory $smtpFactory,
        \Dotdigitalgroup\Email\Helper\Transactional $helper
    ) {
        if (!$message instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }
        $this->message  = $message;
        $this->helper   = $helper;
        $this->smtp = $smtpFactory->create(
            [
            'host' => $this->helper->getSmtpHost(),
            'config' => $this->helper->getTransportConfig()]
        );
    }

    /**
     * @param TransportInterface $subject
     * @param \Closure $proceed
     * @throws \Exception
     *
     * @return null
     */
    public function aroundSendMessage(
        TransportInterface $subject,
        \Closure $proceed
    ) {
        if ($this->helper->isEnabled()) {
            $this->smtp->send($this->message);
        } else {
            $proceed();
        }
    }
}
