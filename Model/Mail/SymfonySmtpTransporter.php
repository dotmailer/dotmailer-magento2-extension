<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\EmailMessage;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\MimePart;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\Auth\LoginAuthenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface as SymfonyTransportInterface;
use Symfony\Component\Mime\Message as SymfonyMimeMessage;
use Magento\Framework\Filesystem\Io\File;

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
     * @var \Magento\Framework\Filesystem\Io\File
     */
    private $fileSystem;

    /**
     * @param Transactional $transactionalEmailSettings
     * @param EmailMessageMethodChecker $emailMessageMethodChecker
     * @param SymfonyMailerFactory $mailerFactory
     * @param File $fileSystem
     */
    public function __construct(
        Transactional $transactionalEmailSettings,
        EmailMessageMethodChecker $emailMessageMethodChecker,
        SymfonyMailerFactory $mailerFactory,
        File $fileSystem
    ) {
        $this->transactionalEmailSettings = $transactionalEmailSettings;
        $this->emailMessageMethodChecker = $emailMessageMethodChecker;
        $this->mailerFactory = $mailerFactory;
        $this->fileSystem = $fileSystem;
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

        $parts = $message->getMessageBody()->getParts();

        // Use Email class to support attachments
        $symfonyEmail = new \Symfony\Component\Mime\Email();

        $contentSet = false;
        // Find HTML or text content
        foreach ($parts as $mimePart) {
            $contentType = $mimePart->getType();
            $disposition = $mimePart->getDisposition();

            if ($disposition === 'attachment') {
                $filename = $mimePart->getFileName();

                if ($filename && (strpos($filename, '/') !== false || strpos($filename, '\\') !== false)) {
                    $fileInfo = $this->fileSystem->getPathInfo($filename);
                    $filename = $fileInfo['basename'];
                }

                $content = $mimePart->getRawContent();

                $symfonyEmail->attach(
                    $content,
                    $filename ?? 'attachment',
                    $contentType ?: 'application/octet-stream'
                );
            } elseif ($contentType && strpos($contentType, 'text/html') !== false) {
                $content = $mimePart->getRawContent();
                $contentSet = true;

                $symfonyEmail->html($content);
            } elseif ($contentType && strpos($contentType, 'text/plain') !== false) {
                $content = $mimePart->getRawContent();
                $contentSet = true;

                $symfonyEmail->text($content);
            }
        }

        if (!$contentSet) {
            throw new LocalizedException(__('Unable to get raw content from message parts'));
        }

        $symfonyMessage = new SymfonyMimeMessage(null, $symfonyEmail->getBody());

        foreach ($message->getHeaders() as $headerName => $headerValue) {
            if ($headerName === 'Date') {
                $symfonyMessage->getHeaders()->addDateHeader($headerName, new \DateTime($headerValue));
            }
            if ($headerName === 'Subject') {
                $symfonyMessage->getHeaders()->addTextHeader('Subject', $headerValue);
            }
            if (in_array($headerName, ['From', 'Reply-to', 'To', 'Cc', 'Bcc'], true)) {
                if (strpos($headerValue, ',') !== false) {
                    $headerValues = explode(',', $headerValue);
                    $symfonyMessage->getHeaders()->addMailboxListHeader($headerName, $headerValues);
                } else {
                    $symfonyMessage->getHeaders()->addMailboxListHeader($headerName, [$headerValue]);
                }
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
