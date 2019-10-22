<?php

namespace Dotdigitalgroup\Email\Controller\Ajax;

use Dotdigitalgroup\Email\Model\Chat\Config;
use Dotdigitalgroup\Email\Model\Chat\Profile\UpdateChatProfile;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Framework\App\ObjectManager;
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

    /**
     * @var UpdateChatProfile
     */
    private $updateChatProfileMock;

    public function setUp()
    {
        parent::setUp();

        $objectManager = ObjectManager::getInstance();

        $this->sessionMock = $this->createMock(Session::class);
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasItems', 'getCustomerEmail', 'setCustomerEmail'])
            ->getMock();

        $this->quoteResourceMock = $this->createMock(QuoteResource::class);

        $this->updateChatProfileMock = $this->createMock(UpdateChatProfile::class);

        $objectManager->addSharedInstance($this->quoteResourceMock, QuoteResource::class);
        $objectManager->addSharedInstance($this->sessionMock, Session::class);
        $objectManager->addSharedInstance($this->updateChatProfileMock, UpdateChatProfile::class);
    }

    public function testExecuteWithNoQuote()
    {
        $this->sessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn(null);

        $this->quoteMock->expects($this->never())
            ->method('hasItems');

        $this->getRequest()->setParam('email', 'chaz@kangaroo.com');
        $this->dispatch('/connector/ajax/emailcapture');
    }

    public function testWithQuote()
    {
        $this->setUpForAvailableQuote();

        $this->getRequest()->setParam('email', 'chaz@kangaroo.com');
        $this->dispatch('/connector/ajax/emailcapture');
    }

    public function testBadEmail()
    {
        $this->sessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->never())
            ->method('hasItems');

        $this->getRequest()->setParam('email', 'datatifileds');
        $this->dispatch('/connector/ajax/emailcapture');
    }

    public function testQuoteAlreadyHasEmail()
    {
        $this->setUpForAvailableQuote('wingman@cauals.com');

        $this->quoteMock->expects($this->never())
            ->method('setCustomerEmail');

        $this->getRequest()->setParam('email', 'chaz@kangaroo.com');
        $this->dispatch('/connector/ajax/emailcapture');
    }

    public function testUpdateChatProfileCookie()
    {
        $profileId = 123456;
        $email = 'chaz@kangaroo.com';
        $_COOKIE[Config::COOKIE_CHAT_PROFILE] = $profileId;

        $this->setUpForAvailableQuote();

        $this->updateChatProfileMock->expects($this->once())
            ->method('update')
            ->with($profileId, $email);

        $this->getRequest()->setParam('email', $email);

        $this->dispatch('/connector/ajax/emailcapture');
    }

    private function setUpForAvailableQuote(string $customerEmail = null)
    {
        $this->sessionMock->expects($this->once())
            ->method('getQuote')
            ->willReturn($this->quoteMock);

        $this->quoteMock->expects($this->once())
            ->method('hasItems')
            ->willReturn(true);

        $this->quoteMock->expects($this->once())
            ->method('getCustomerEmail')
            ->willReturn($customerEmail);

        if (!$customerEmail) {
            $this->quoteResourceMock->expects($this->once())
                ->method('save')
                ->with($this->quoteMock);
        }
    }
}
