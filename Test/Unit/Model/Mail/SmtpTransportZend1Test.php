<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Mail\SmtpTransportZend1;
use Dotdigitalgroup\Email\Model\Mail\ZendMailTransportSmtp1Factory;
use Zend_Mail;
use Zend_Mail_Transport_Smtp;

class SmtpTransportZend1Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Transactional|PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionalEmailSettings;

    /**
     * @var ZendMailTransportSmtp1Factory|PHPUnit_Framework_MockObject_MockObject
     */
    private $zendMailTransportSmtp1Factory;

    /**
     * @var SmtpTransportZend1
     */
    private $smtpTransportZend1;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * Prepare data
     */
    protected function setUp() :void
    {
        $this->loggerMock = $this->createMock(Logger::class);

        $this->transactionalEmailSettings = $this->getMockBuilder(
            Transactional::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->zendMailTransportSmtp1Factory = $this->getMockBuilder(
            ZendMailTransportSmtp1Factory::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->smtpTransportZend1 = new SmtpTransportZend1(
            $this->transactionalEmailSettings,
            $this->zendMailTransportSmtp1Factory,
            $this->loggerMock
        );
    }

    /**
     * @throws \Zend_Mail_Transport_Exception
     */
    public function testSendUsesZend1MailTransportSmtp()
    {
        $message = new Zend_Mail();
        $storeId = 124;
        $host = '127.0.0.1';
        $transportConfig = [];

        $this->transactionalEmailSettings->expects($this->once())
            ->method('getSmtpHost')
            ->with($storeId)
            ->willReturn($host);

        $this->transactionalEmailSettings->expects($this->once())
            ->method('getTransportConfig')
            ->with($storeId)
            ->willReturn($transportConfig);

        $zendMailTransportSmtp = $this->getMockBuilder(
            Zend_Mail_Transport_Smtp::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->zendMailTransportSmtp1Factory->expects($this->once())
            ->method('create')
            ->with($host, $transportConfig)
            ->willReturn($zendMailTransportSmtp);

        $zendMailTransportSmtp->expects($this->once())
            ->method('send')
            ->with($message);

        $this->smtpTransportZend1->send($message, $storeId);
    }
}
