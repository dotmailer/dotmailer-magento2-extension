<?php

namespace Dotdigitalgroup\Email\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;

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
     * @param Transactional $transactionalEmailSettings
     * @param ZendMailTransportSmtp1Factory $zendMailTransportSmtp1Factory
     */
    public function __construct(
        Transactional $transactionalEmailSettings,
        ZendMailTransportSmtp1Factory $zendMailTransportSmtp1Factory
    ) {
        $this->transactionalEmailSettings = $transactionalEmailSettings;
        $this->zendMailTransportSmtp1Factory = $zendMailTransportSmtp1Factory;
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
        $host = $this->transactionalEmailSettings->getSmtpHost($storeId);
        $config = $this->transactionalEmailSettings->getTransportConfig($storeId);

        $smtp = $this->zendMailTransportSmtp1Factory->create($host, $config);
        $smtp->send($message);
    }
}
