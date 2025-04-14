<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\EmailMessage;
use Magento\Framework\Mail\EmailMessageInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\Auth\LoginAuthenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface as SymfonyTransportInterface;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Message as SymfonyMimeMessage;
use Symfony\Component\Mime\Part\TextPart;

class SymfonySmtpTransporter
{
    /**
     * @var Transactional
     */
    private $transactionalEmailSettings;

    /**
     * @var EmailMessageMethodChecker
     */
    private $emailMessageMethodChecker;

    /**
     * @var SymfonyMailerFactory
     */
    private SymfonyMailerFactory $mailerFactory;

    /**
     * @param Transactional $transactionalEmailSettings
     * @param EmailMessageMethodChecker $emailMessageMethodChecker
     * @param SymfonyMailerFactory $mailerFactory
     */
    public function __construct(
        Transactional $transactionalEmailSettings,
        EmailMessageMethodChecker $emailMessageMethodChecker,
        SymfonyMailerFactory $mailerFactory
    ) {
        $this->transactionalEmailSettings = $transactionalEmailSettings;
        $this->emailMessageMethodChecker = $emailMessageMethodChecker;
        $this->mailerFactory = $mailerFactory;
    }

    /**
     * Execute.
     *
     * @param EmailMessageInterface $message
     * @param int $storeId
     *
     * @throws TransportExceptionInterface|LocalizedException
     */
    public function execute(EmailMessageInterface $message, int $storeId)
    {
        try {
            $message = $this->getOrConvertMimeMessage($message);
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Unable to get or convert mime message: %1', $e->getMessage())
            );
        }

        $transport = $this->createSmtpTransport($storeId);
        $this->sendMessage($message, $transport);
    }

    /**
     * Extract message.
     *
     * Returns \Symfony\Component\Mime\Message (2.4.8 and above) or
     * creates one from a \Laminas\Mime\Message (2.4.7 and below)
     *
     * @param EmailMessageInterface $message
     *
     * @return SymfonyMimeMessage
     * @throws \DateMalformedStringException
     */
    private function getOrConvertMimeMessage(EmailMessageInterface $message): SymfonyMimeMessage
    {
        if ($this->emailMessageMethodChecker->hasGetSymfonyMessageMethod($message)) {
            /** @var EmailMessage $message */
            return $message->getSymfonyMessage();
        }

        $mimeMessage = $message->getMessageBody();
        $messageHeaders = $message->getHeaders();

        $symfonyHeaders = new Headers();
        foreach ($messageHeaders as $headerName => $headerValue) {
            if ($headerName === 'Date') {
                $headerValue = new \DateTime($headerValue);
            }
            // required for symfony/mime 5.x, which we need to hold at for PHP 7.4 support
            if (in_array($headerName, ['From', 'Reply-to', 'To', 'Cc', 'Bcc'], true)) {
                $headerValue = [$headerValue];
            }
            $symfonyHeaders->addHeader($headerName, $headerValue);
        }

        $symfonyBody = new TextPart($mimeMessage->getMessage());

        return new SymfonyMimeMessage($symfonyHeaders, $symfonyBody);
    }

    /**
     * Create SMTP object.
     *
     * @param int $storeId
     *
     * @return EsmtpTransport
     * @throws LocalizedException
     */
    private function createSmtpTransport(int $storeId): SymfonyTransportInterface
    {
        $smtpHost = $this->transactionalEmailSettings->getSmtpHost($storeId);
        $smtpPort = $this->transactionalEmailSettings->getSmtpPort($storeId);
        $smtpUsername = $this->transactionalEmailSettings->getSmtpUsername($storeId);
        $smtpPassword = $this->transactionalEmailSettings->getSmtpPassword($storeId);

        if (empty($smtpHost) || empty($smtpPort) || empty($smtpUsername) || empty($smtpPassword)) {
            throw new LocalizedException(
                __('Dotdigital SMTP options are not correctly defined')
            );
        }

        $transport = new EsmtpTransport(
            $smtpHost,
            (int) $smtpPort,
            false
        );
        $transport->setUsername($smtpUsername);
        $transport->setPassword($smtpPassword);
        $transport->addAuthenticator(new LoginAuthenticator());

        return $transport;
    }

    /**
     * Send message.
     *
     * @param SymfonyMimeMessage $message
     * @param SymfonyTransportInterface $transport
     *
     * @throws TransportExceptionInterface
     */
    private function sendMessage(SymfonyMimeMessage $message, SymfonyTransportInterface $transport)
    {
        $this->mailerFactory->create($transport)
            ->send($message);
    }
}
