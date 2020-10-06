<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Model\Mail\SmtpTransportZend2;
use Dotdigitalgroup\Email\Model\Mail\ZendMailTransportSmtp2Factory;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp;

class SmtpTransportZend2Test extends \PHPUnit\Framework\TestCase
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
     * @var SmtpTransportZend2
     */
    private $smtpTransportZend2;

    /**
     * Prepare data
     */
    protected function setUp() :void
    {
        $this->transactionalEmailSettings = $this->getMockBuilder(
            Transactional::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->zendMailTransportSmtp2Factory = $this->getMockBuilder(
            ZendMailTransportSmtp2Factory::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->smtpTransportZend2 = new SmtpTransportZend2(
            $this->transactionalEmailSettings,
            $this->zendMailTransportSmtp2Factory
        );
    }

    /**
     */
    public function testSendUsesZend2MailTransportSmtp()
    {
        $message = new Message();
        $storeId = 224;
        $smtpOptions = [];

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

        $zendMail2TransportSmtp->expects($this->once())
            ->method('send')
            ->with($message);

        $this->smtpTransportZend2->send($message, $storeId);
    }
}
