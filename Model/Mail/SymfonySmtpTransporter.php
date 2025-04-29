<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\EmailMessage;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\MimeInterface;
use Magento\Framework\Mail\MimePart;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\Auth\LoginAuthenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface as SymfonyTransportInterface;
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
     * @throws \DateMalformedStringException|LocalizedException
     */
    private function getOrConvertMimeMessage(EmailMessageInterface $message): SymfonyMimeMessage
    {
        if ($this->emailMessageMethodChecker->hasGetSymfonyMessageMethod($message)) {
            /** @var EmailMessage $message */
            return $message->getSymfonyMessage();
        }

        $messageBody = '';
        foreach ($message->getMessageBody()->getParts() as $part) {
            $messageBody = $part->getRawContent();
            break;
        }

        if (empty($messageBody)) {
            throw new LocalizedException(__('Unable to get raw content from message parts'));
        }

        $symfonyBody = new TextPart(
            $messageBody,
            MimePart::CHARSET_UTF8,
            'html',
            MimeInterface::ENCODING_QUOTED_PRINTABLE
        );
        $symfonyBody->setDisposition('inline');
        $symfonyMessage = new SymfonyMimeMessage(null, $symfonyBody);

        foreach ($message->getHeaders() as $headerName => $headerValue) {
            if ($headerName === 'Date') {
                $symfonyMessage->getHeaders()->addDateHeader($headerName, new \DateTime($headerValue));
            }
            if ($headerName === 'Subject') {
                $symfonyMessage->getHeaders()->addTextHeader('Subject', $headerValue);
            }
            if (in_array($headerName, ['From', 'Reply-to', 'To', 'Cc', 'Bcc'], true)) {
                $symfonyMessage->getHeaders()->addMailboxListHeader($headerName, [$headerValue]);
            }
        }

        return $symfonyMessage;
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
