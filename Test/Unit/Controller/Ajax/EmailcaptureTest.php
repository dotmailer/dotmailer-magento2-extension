<?php

namespace Dotdigitalgroup\Email\Test\Unit\Controller\Ajax;

use Dotdigitalgroup\Email\Api\Logger\LoggerInterface;
use Dotdigitalgroup\Email\Controller\Ajax\Emailcapture;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Session;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailcaptureTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $loggerMock;

    /**
     * @var Session
     */
    private $sessionMock;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepositoryMock;

    /**
     * @var RequestInterface
     */
    private $requestMock;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactoryMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    /**
     * @var Emailcapture
     */
    private $controller;

    public function setUp() :void
    {
        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->sessionMock = $this->createMock(Session::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->resultJsonFactoryMock = $this->createMock(JsonFactory::class);
        $this->quoteMock = $this->createMock(Quote::class);

        $resultJsonMock = $this->createMock(Json::class);
        $this->resultJsonFactoryMock->method('create')->willReturn($resultJsonMock);

        $this->requestMock = $this->createMock(RequestInterface::class);
        $contextMock = $this->createMock(\Magento\Framework\App\Action\Context::class);
        $contextMock->method('getRequest')->willReturn($this->requestMock);

        $this->controller = new Emailcapture(
            $this->loggerMock,
            $this->cartRepositoryMock,
            $this->sessionMock,
            $contextMock,
            $this->resultJsonFactoryMock
        );
    }

    public function testExecuteWithNoQuote()
    {
        $this->sessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn(null);

        $this->quoteMock->expects($this->never())
            ->method('hasItems');

        $this->requestMock->method('getParam')
            ->with('email')
            ->willReturn('chaz@kangaroo.com');

        $this->controller->execute();
    }

    public function testWithQuote()
    {
        $this->setUpForAvailableQuote();

        $this->cartRepositoryMock->expects($this->once())
            ->method('save');

        $this->requestMock->method('getParam')
            ->with('email')
            ->willReturn('chaz@kangaroo.com');

        $this->controller->execute();
    }

    public function testBadEmail()
    {
        $this->sessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->never())
            ->method('hasItems');

        $this->requestMock->method('getParam')
            ->with('email')
            ->willReturn('datatifileds');

        $this->controller->execute();
    }

    public function testQuoteAlreadyHasEmailButIsDifferent()
    {
        $this->setUpForAvailableQuote('wingman@cauals.com');

        $this->cartRepositoryMock->expects($this->once())
            ->method('save');

        $this->requestMock->method('getParam')
            ->with('email')
            ->willReturn('chaz@kangaroo.com');

        $this->controller->execute();
    }

    public function testQuoteAlreadyHasSameEmail()
    {
        $this->sessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->once())
            ->method('hasItems')
            ->willReturn(true);

        $this->quoteMock->expects($this->once())
            ->method('__call')
            ->with('getCustomerEmail')
            ->willReturn('chaz@kangaroo.com');

        $this->cartRepositoryMock->expects($this->never())
            ->method('save');

        $this->requestMock->method('getParam')
            ->with('email')
            ->willReturn('chaz@kangaroo.com');

        $this->controller->execute();
    }

    private function setUpForAvailableQuote(?string $customerEmail = null)
    {
        $this->sessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->once())
            ->method('hasItems')
            ->willReturn(true);

        $matcher = $this->exactly(2);
        $this->quoteMock->expects($matcher)
            ->method('__call')
            ->willReturnCallback(function () use ($matcher, $customerEmail) {
                return match ($matcher->numberOfInvocations()) {
                    0 => ['getCustomerEmail'],
                    1 => ['setCustomerEmail'],
                };
            })
            ->willReturnOnConsecutiveCalls(
                $customerEmail,
                null
            );
    }
}
