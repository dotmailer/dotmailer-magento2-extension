<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Mail;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\TransportInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SmtpTransporter
{
    /**
     * @var SymfonySmtpTransporter
     */
    private $symfonySmtpTransporter;

    /**
     * SmtpTransporter constructor.
     *
     * @param SymfonySmtpTransporter $symfonySmtpTransporter
     */
    public function __construct(
        SymfonySmtpTransporter $symfonySmtpTransporter
    ) {
        $this->symfonySmtpTransporter = $symfonySmtpTransporter;
    }

    /**
     * Send.
     *
     * @param TransportInterface $subject
     * @param int $storeId
     *
     * @throws LocalizedException|TransportExceptionInterface
     */
    public function send($subject, $storeId)
    {
        /** @var EmailMessageInterface $message */
        $message = $subject->getMessage();
        $this->symfonySmtpTransporter->execute($message, $storeId);
    }
}
