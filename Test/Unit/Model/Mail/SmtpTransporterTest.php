<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Model\Mail\SmtpTransporter;
use Dotdigitalgroup\Email\Model\Mail\ZendMailTransportSmtp2Factory;
use Magento\Framework\Mail\TransportInterface;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp;

class SmtpTransporterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Transactional|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionalEmailSettings;

    /**
     * @var ZendMailTransportSmtp2Factory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $zendMailTransportSmtp2Factory;

    /**
     * @var SmtpTransporter
     */
    private $smtpTransporter;

    /**
     * @var TransportInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $subject;

    /**
     * Prepare data
     */
    protected function setUp() :void
    {
        $this->subject = $this->getMockBuilder(
            TransportInterface::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->transactionalEmailSettings = $this->getMockBuilder(
            Transactional::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->zendMailTransportSmtp2Factory = $this->getMockBuilder(
            ZendMailTransportSmtp2Factory::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->smtpTransporter = new SmtpTransporter(
            $this->transactionalEmailSettings,
            $this->zendMailTransportSmtp2Factory
        );
    }

    /**
     */
    public function testSendViaSmtpTransporter()
    {
        $message = new Message();
        $storeId = 224;
        $smtpOptions = [];

        $magentoFrameworkMessage = $this->getMockBuilder(
            \Magento\Framework\Mail\EmailMessageInterface::class
        )->disableOriginalConstructor(
        )->getMock();

        $magentoFrameworkMessage->expects($this->once())
            ->method('getRawMessage')
            ->willReturn($message->toString());

        $this->subject->expects($this->once())
            ->method('getMessage')
            ->willReturn($magentoFrameworkMessage);

        $this->transactionalEmailSettings->expects($this->once())
            ->method('getSmtpOptions')
            ->with($storeId)
            ->willReturn($smtpOptions);

        $zendMail2TransportSmtp = $this->getMockBuilder(
            Smtp::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->zendMailTransportSmtp2Factory->expects($this->once())
            ->method('create')
            ->with($smtpOptions)
            ->willReturn($zendMail2TransportSmtp);

        // Not checking args passed in. Can't mock static Message::fromString
        $zendMail2TransportSmtp->expects($this->once())
            ->method('send');

        $this->smtpTransporter->send($this->subject, $storeId);
    }
}
