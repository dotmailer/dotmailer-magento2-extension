<?php

namespace Dotdigitalgroup\Email\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;

class SmtpTransportZend2
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
     * @param \Magento\Framework\Mail\Message $message
     * @param int $storeId
     */
    public function send($message, $storeId)
    {
        $smtpOptions = $this->transactionalEmailSettings->getSmtpOptions($storeId);
        $smtp = $this->zendMailTransportSmtp2Factory->create($smtpOptions);
        $smtp->send($message);
    }
}
