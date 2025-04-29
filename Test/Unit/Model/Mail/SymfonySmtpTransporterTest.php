<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Model\Mail\EmailMessageMethodChecker;
use Dotdigitalgroup\Email\Model\Mail\SymfonyMailerFactory;
use Dotdigitalgroup\Email\Model\Mail\SymfonySmtpTransporter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\EmailMessage;
use Magento\Framework\Mail\MimeMessageInterface;
use Magento\Framework\Mail\MimePartInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\TransportInterface as SymfonyTransportInterface;
use Symfony\Component\Mime\Message as SymfonyMimeMessage;

class SymfonySmtpTransporterTest extends TestCase
{
    /**
     * @var Transactional|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transactionalEmailSettingsMock;

    /**
     * @var EmailMessageMethodChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $emailMessageMethodCheckerMock;

    /**
     * @var SymfonyMailerFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $symfonyMailerFactoryMock;

    /**
     * @var SymfonySmtpTransporter
     */
    private $symfonySmtpTransporter;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        $this->transactionalEmailSettingsMock = $this->createMock(Transactional::class);
        $this->emailMessageMethodCheckerMock = $this->createMock(EmailMessageMethodChecker::class);
        $this->symfonyMailerFactoryMock = $this->createMock(SymfonyMailerFactory::class);

        $this->symfonySmtpTransporter = new SymfonySmtpTransporter(
            $this->transactionalEmailSettingsMock,
            $this->emailMessageMethodCheckerMock,
            $this->symfonyMailerFactoryMock
        );
    }

    /**
     * Test the execute method.
     */
    public function testExecuteWithSymfonyMimeMessage()
    {
        $storeId = 1;

        $emailMessage = $this->createMock(EmailMessage::class);
        $symfonyMimeMessage = $this->createMock(SymfonyMimeMessage::class);

        $this->emailMessageMethodCheckerMock->expects($this->once())
            ->method('hasGetSymfonyMessageMethod')
            ->with($emailMessage)
            ->willReturn(true);

        $this->transactionalEmailSettingsMock->expects($this->once())
            ->method('getSmtpHost')
            ->with($storeId)
            ->willReturn('smtp.example.com');

        $this->transactionalEmailSettingsMock->expects($this->once())
            ->method('getSmtpPort')
            ->with($storeId)
            ->willReturn(587);

        $this->transactionalEmailSettingsMock->expects($this->once())
            ->method('getSmtpUsername')
            ->with($storeId)
            ->willReturn('user@example.com');

        $this->transactionalEmailSettingsMock->expects($this->once())
            ->method('getSmtpPassword')
            ->with($storeId)
            ->willReturn('password');

        $emailMessage->expects($this->once())
            ->method('getSymfonyMessage')
            ->willReturn($symfonyMimeMessage);

        // Create a real Mailer instance with a mock transport (because Mailer is final and connot be mocked)
        $symfonyTransport = $this->createMock(SymfonyTransportInterface::class);
        $realMailer = new Mailer($symfonyTransport);

        $this->symfonyMailerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($realMailer);

        $this->symfonySmtpTransporter->execute($emailMessage, $storeId);
    }

    /**
     * Test the execute method.
     */
    public function testExecuteWithLaminasMimeMessage()
    {
        $storeId = 1;

        $emailMessage = $this->createMock(EmailMessage::class);
        $this->emailMessageMethodCheckerMock->expects($this->once())
            ->method('hasGetSymfonyMessageMethod')
            ->with($emailMessage)
            ->willReturn(false);

        $mimeMessageMock = $this->createMock(MimeMessageInterface::class);
        $mimePartMock = $this->createMock(MimePartInterface::class);

        $emailMessage->expects($this->once())
            ->method('getMessageBody')
            ->willReturn($mimeMessageMock);

        $mimeMessageMock->expects($this->once())
            ->method('getParts')
            ->willReturn([$mimePartMock]);

        $mimePartMock->expects($this->once())
            ->method('getRawContent')
            ->willReturn('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" etc.');

        $emailMessage->expects($this->once())
            ->method('getHeaders')
            ->willReturn([
                'From' => 'sender@example.com',
                'To' => 'recipient@example.com',
                'Subject' => 'Test Email',
                'Date' => '2023-10-01 12:00:00',
            ]);

        $this->transactionalEmailSettingsMock->expects($this->once())
            ->method('getSmtpHost')
            ->with($storeId)
            ->willReturn('smtp.example.com');

        $this->transactionalEmailSettingsMock->expects($this->once())
            ->method('getSmtpPort')
            ->with($storeId)
            ->willReturn(587);

        $this->transactionalEmailSettingsMock->expects($this->once())
            ->method('getSmtpUsername')
            ->with($storeId)
            ->willReturn('user@example.com');

        $this->transactionalEmailSettingsMock->expects($this->once())
            ->method('getSmtpPassword')
            ->with($storeId)
            ->willReturn('password');

        // Create a real Mailer instance with a mock transport
        $symfonyTransport = $this->createMock(SymfonyTransportInterface::class);
        $realMailer = new Mailer($symfonyTransport);

        $this->symfonyMailerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($realMailer);

        $this->symfonySmtpTransporter->execute($emailMessage, $storeId);
    }

    /**
     * Test the execute method.
     */
    public function testExceptionThrownIfNoRawContentFromPart()
    {
        $storeId = 1;

        $emailMessage = $this->createMock(EmailMessage::class);
        $this->emailMessageMethodCheckerMock->expects($this->once())
            ->method('hasGetSymfonyMessageMethod')
            ->with($emailMessage)
            ->willReturn(false);

        $mimeMessageMock = $this->createMock(MimeMessageInterface::class);
        $mimePartMock = $this->createMock(MimePartInterface::class);

        $emailMessage->expects($this->once())
            ->method('getMessageBody')
            ->willReturn($mimeMessageMock);

        $mimeMessageMock->expects($this->once())
            ->method('getParts')
            ->willReturn([$mimePartMock]);

        $mimePartMock->expects($this->once())
            ->method('getRawContent')
            ->willReturn('');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Unable to get raw content from message parts');

        $this->symfonySmtpTransporter->execute($emailMessage, $storeId);
    }

    /**
     * Test the execute method throws exception on invalid SMTP settings.
     */
    public function testExecuteThrowsExceptionOnInvalidSmtpSettings()
    {
        $storeId = 1;

        $emailMessage = $this->createMock(EmailMessage::class);

        $this->emailMessageMethodCheckerMock->expects($this->once())
            ->method('hasGetSymfonyMessageMethod')
            ->with($emailMessage)
            ->willReturn(true);

        $this->transactionalEmailSettingsMock->expects($this->once())
            ->method('getSmtpHost')
            ->with($storeId)
            ->willReturn('');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Dotdigital SMTP options are not correctly defined');

        $emailMessage = $this->createMock(EmailMessage::class);
        $this->symfonySmtpTransporter->execute($emailMessage, $storeId);
    }
}
