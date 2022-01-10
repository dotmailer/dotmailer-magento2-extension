<?php

namespace Dotdigitalgroup\Email\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;
use Magento\Framework\Mail\TransportInterface;
use Zend\Mail\Message;

class SmtpTransporter
{
    /**
     * @var Transactional
     */
    private $transactionalEmailSettings;

    /**
     * @var ZendMailTransportSmtp2Factory
     */
    private $zendMailTransportSmtp2Factory;

    /**
     * Default encoding
     */
    const ENCODING = 'utf-8';

    /**
     * SmtpTransporter constructor.
     * @param Transactional $transactionalEmailSettings
     * @param ZendMailTransportSmtp2Factory $zendMailTransportSmtp2Factory
     */
    public function __construct(
        Transactional $transactionalEmailSettings,
        ZendMailTransportSmtp2Factory $zendMailTransportSmtp2Factory
    ) {
        $this->transactionalEmailSettings = $transactionalEmailSettings;
        $this->zendMailTransportSmtp2Factory = $zendMailTransportSmtp2Factory;
    }

    /**
     * @param TransportInterface $subject
     * @param int $storeId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function send($subject, $storeId)
    {
        $message = $this->extractZendMailMessage($subject);
        $this->sendMessage($message, $storeId);
    }

    /**
     * @param TransportInterface $subject
     * @return Message
     */
    private function extractZendMailMessage($subject)
    {
        $message = $subject->getMessage();
        return Message::fromString($message->getRawMessage());
    }

    /**
     * @param Message $message
     * @param int $storeId
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function sendMessage($message, $storeId)
    {
        $smtpOptions = $this->transactionalEmailSettings->getSmtpOptions($storeId);
        $smtp = $this->zendMailTransportSmtp2Factory->create($smtpOptions);
        $message->setEncoding(self::ENCODING);
        $smtp->send($message);
    }
}
