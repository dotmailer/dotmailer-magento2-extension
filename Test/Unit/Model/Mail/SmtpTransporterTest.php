<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Mail;

use Dotdigitalgroup\Email\Model\Mail\SmtpTransporter;
use Dotdigitalgroup\Email\Model\Mail\SymfonySmtpTransporter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\TransportInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SmtpTransporterTest extends TestCase
{
    /**
     * @var SymfonySmtpTransporter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $symfonySmtpTransporter;

    /**
     * @var SmtpTransporter
     */
    private $smtpTransporter;

    /**
     * Set up the test.
     */
    protected function setUp(): void
    {
        $this->symfonySmtpTransporter = $this->createMock(SymfonySmtpTransporter::class);

        $this->smtpTransporter = new SmtpTransporter(
            $this->symfonySmtpTransporter
        );
    }

    /**
     * Test the send method.
     *
     * @throws LocalizedException
     * @throws TransportExceptionInterface
     */
    public function testSend()
    {
        $storeId = 1;

        $emailMessage = $this->createMock(EmailMessageInterface::class);
        $transportInterface = $this->createMock(TransportInterface::class);

        $transportInterface->expects($this->once())
            ->method('getMessage')
            ->willReturn($emailMessage);

        $this->symfonySmtpTransporter->expects($this->once())
            ->method('execute')
            ->with($emailMessage, $storeId);

        $this->smtpTransporter->send($transportInterface, $storeId);
    }
}
