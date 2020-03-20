<?php

namespace Dotdigitalgroup\Email\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Logger\Logger;

class SmtpTransportZend1
{
    /**
     * @var Transactional
     */
    private $transactionalEmailSettings;

    /**
     * @var ZendMailTransportSmtp1Factory
     */
    private $zendMailTransportSmtp1Factory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * SmtpTransportZend1 constructor.
     * @param Transactional $transactionalEmailSettings
     * @param ZendMailTransportSmtp1Factory $zendMailTransportSmtp1Factory
     * @param Logger $logger
     */
    public function __construct(
        Transactional $transactionalEmailSettings,
        ZendMailTransportSmtp1Factory $zendMailTransportSmtp1Factory,
        Logger $logger
    ) {
        $this->transactionalEmailSettings = $transactionalEmailSettings;
        $this->zendMailTransportSmtp1Factory = $zendMailTransportSmtp1Factory;
        $this->logger = $logger;
    }

    /**
     * @param \Zend_Mail $message
     * @param int $storeId
     *
     * @return void
     * @throws \Zend_Mail_Transport_Exception
     */
    public function send($message, $storeId)
    {
        try {
            $host = $this->transactionalEmailSettings->getSmtpHost($storeId);
        } catch (\Exception $e) {
            $this->logger->debug('Smtp Host is not defined', [$e]);
            return;
        }

        $config = $this->transactionalEmailSettings->getTransportConfig($storeId);

        $smtp = $this->zendMailTransportSmtp1Factory->create($host, $config);
        $smtp->send($message);
    }
}
