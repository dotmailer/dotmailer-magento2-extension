<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Mail;

use Dotdigitalgroup\Email\Helper\Transactional;
use Dotdigitalgroup\Email\Model\Mail\SmtpTransportAdapter;
use Dotdigitalgroup\Email\Model\Mail\SmtpTransportZend1;
use Dotdigitalgroup\Email\Model\Mail\SmtpTransportZend2;
use Magento\Framework\Mail\TransportInterface;
use Zend_Mail;

class SmtpTransportAdapterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var bool
     */
    public static $shouldOverrideMethodExists = false;

    /**
     * @var Transactional|PHPUnit_Framework_MockObject_MockObject
     */
    private $transactionalEmailSettingsMock;

    /**
     * @var SmtpTransportZend1|PHPUnit_Framework_MockObject_MockObject
     */
    private $smtpTransportZendV1Mock;

    /**
     * @var SmtpTransportZend2|PHPUnit_Framework_MockObject_MockObject
     */
    private $smtpTransportZendV2Mock;

    /**
     * @var SmtpTransportAdapter
     */
    private $smtpTransportAdapter;

    /**
     * @var TransportInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private $subject;

    /**
     * @var int
     */
    private $storeId = 123;

    /**
     * Prepare data
     */
    protected function setUp() :void
    {
        $this->transactionalEmailSettingsMock = $this->getMockBuilder(
            Transactional::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->smtpTransportZendV1Mock = $this->getMockBuilder(
            SmtpTransportZend1::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->smtpTransportZendV2Mock = $this->getMockBuilder(
            SmtpTransportZend2::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->subject = $this->getMockBuilder(
            TransportInterface::class
        )->disableOriginalConstructor(
        )->getMock();

        $this->smtpTransportAdapter = new SmtpTransportAdapter(
            $this->transactionalEmailSettingsMock,
            $this->smtpTransportZendV1Mock,
            $this->smtpTransportZendV2Mock
        );
    }

    /**
     * @throws \ReflectionException
     * @throws \Zend_Mail_Transport_Exception
     */
    public function testSmtpTransportZend1UsedIfMessageIsZendMail()
    {
        $message = new Zend_Mail();

        $this->subject->expects($this->once())->method('getMessage')->willReturn($message);

        $this->smtpTransportZendV1Mock->expects($this->once())->method('send')->with($message, $this->storeId);
        $this->smtpTransportZendV2Mock->expects($this->never())->method('send');

        $this->smtpTransportAdapter->send($this->subject, $this->storeId);
    }

    /**
     * @throws \ReflectionException
     * @throws \Zend_Mail_Transport_Exception
     */
    public function testMessageIsAccessedViaReflectionIfAccesssorMethodNotFound()
    {
        self::$shouldOverrideMethodExists = true;

        $message = new Zend_Mail();

        $subject = new Magento21FrameworkMailTransportMock();
        $subject->setMessage($message);

        $this->smtpTransportZendV1Mock->expects($this->once())->method('send')->with($message, $this->storeId);
        $this->smtpTransportZendV2Mock->expects($this->never())->method('send');

        $this->smtpTransportAdapter->send($subject, $this->storeId);
    }

    /**
     * @throws \ReflectionException
     * @throws \Zend_Mail_Transport_Exception
     */
    public function testSmtpTransportZend2UsedIfMessageIsNotZendMail()
    {
        $zendMessage = new \Zend\Mail\Message();
        $magentoFrameworkMessage = $this->getMockBuilder(
            \Magento\Framework\Mail\Message::class
        )->disableOriginalConstructor(
        )->getMock();

        $magentoFrameworkMessage->expects($this->once())
            ->method('getRawMessage')
            ->willReturn($zendMessage->toString());

        $this->subject->expects($this->once())->method('getMessage')->willReturn($magentoFrameworkMessage);

        $this->smtpTransportZendV1Mock->expects($this->never())->method('send');
        // Not checking args passed in. Can't mock static Message::fromString
        $this->smtpTransportZendV2Mock->expects($this->once())->method('send');

        $this->smtpTransportAdapter->send($this->subject, $this->storeId);
    }
}
