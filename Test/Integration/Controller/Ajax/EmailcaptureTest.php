<?php

namespace Dotdigitalgroup\Email\Test\Integration\Controller\Ajax;

use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Checkout\Model\Session;

class EmailcaptureTest extends AbstractController
{
    use MocksApiResponses;

    /**
     * @var Session
     */
    private $sessionMock;

    /**
     * @var Quote
     */
    private $quoteMock;

    /**
     * @var QuoteResource
     */
    private $quoteResourceMock;

    public function setUp() :void
    {
        parent::setUp();

        $objectManager = ObjectManager::getInstance();

        $this->sessionMock = $this->createMock(Session::class);
        $this->quoteMock = $this->createMock(Quote::class);
        $this->quoteResourceMock = $this->createMock(QuoteResource::class);

        $objectManager->addSharedInstance($this->quoteResourceMock, QuoteResource::class);
        $objectManager->addSharedInstance($this->sessionMock, Session::class);
    }

    public function testExecuteWithNoQuote()
    {
        $this->sessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn(null);

        $this->quoteMock->expects($this->never())
            ->method('hasItems');

        $this->getRequest()->setParams(['email' => 'chaz@kangaroo.com'])->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('/connector/ajax/emailcapture');
    }

    public function testWithQuote()
    {
        $this->setUpForAvailableQuote();

        $this->getRequest()->setParams(['email' => 'chaz@kangaroo.com'])->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('/connector/ajax/emailcapture');
    }

    public function testBadEmail()
    {
        $this->sessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->never())
            ->method('hasItems');

        $this->getRequest()->setParams(['email' => 'datatifileds'])->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('/connector/ajax/emailcapture');
    }

    public function testQuoteAlreadyHasEmailButIsDifferent()
    {
        $this->setUpForAvailableQuote('wingman@cauals.com');

        $this->getRequest()->setParams(['email' => 'chaz@kangaroo.com'])->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('/connector/ajax/emailcapture');
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
            ->willReturn('wingman@cauals.com');

        $this->getRequest()->setParams(['email' => 'wingman@cauals.com'])->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('/connector/ajax/emailcapture');
    }

    protected function setUpForAvailableQuote(string $customerEmail = null)
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
