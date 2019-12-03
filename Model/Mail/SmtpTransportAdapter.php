<?php

namespace Dotdigitalgroup\Email\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;
use Magento\Framework\Mail\TransportInterface;
use Zend\Mail\Message;
use Zend_Mail;

class SmtpTransportAdapter
{
    /**
     * @var Transactional
     */
    private $transactionalEmailSettings;

    /**
     * @var SmtpTransportZend1
     */
    private $smtpTransportZendV1;

    /**
     * @var SmtpTransportZend2
     */
    private $smtpTransportZendV2;

    /**
     * SmtpTransportAdapter constructor.
     *
     * @param Transactional $transactionalEmailSettings
     * @param SmtpTransportZend1 $smtpTransportZendV1
     * @param SmtpTransportZend2 $smtpTransportZendV2
     */
    public function __construct(
        Transactional $transactionalEmailSettings,
        SmtpTransportZend1 $smtpTransportZendV1,
        SmtpTransportZend2 $smtpTransportZendV2
    ) {
        $this->transactionalEmailSettings = $transactionalEmailSettings;
        $this->smtpTransportZendV1 = $smtpTransportZendV1;
        $this->smtpTransportZendV2 = $smtpTransportZendV2;
    }

    /**
     * @param TransportInterface $subject
     * @param int $storeId
     *
     * @throws \ReflectionException
     * @throws \Zend_Mail_Transport_Exception
     */
    public function send($subject, $storeId)
    {
        $message = $this->getMessage($subject);
        $this->sendMessage($message, $storeId);
    }

    /**
     * @param TransportInterface $subject
     *
     * @return Zend_Mail|\Magento\Framework\Mail\Message
     * @throws \ReflectionException
     */
    private function getMessage($subject)
    {
        if (method_exists($subject, 'getMessage')) {
            $message = $subject->getMessage();
        } else {
            $message = $this->useReflectionToGetMessage($subject);
        }

        return $message;
    }

    /**
     * @param Zend_Mail|\Magento\Framework\Mail\Message $message
     * @param int $storeId
     *
     * @throws \Zend_Mail_Transport_Exception
     */
    private function sendMessage($message, $storeId)
    {
        if ($message instanceof \Zend_Mail) {
            $this->smtpTransportZendV1->send(
                $message,
                $storeId
            );
        } else {
            $this->smtpTransportZendV2->send(
                Message::fromString($message->getRawMessage()),
                $storeId
            );
        }
    }

    /**
     * @param TransportInterface $subject
     *
     * @return Zend_Mail
     * @throws \ReflectionException
     */
    private function useReflectionToGetMessage($subject)
    {
        $reflection = new \ReflectionClass($subject);
        $property   = $reflection->getProperty('_message');
        $property->setAccessible(true);
        $message = $property->getValue($subject);

        return $message;
    }
}
