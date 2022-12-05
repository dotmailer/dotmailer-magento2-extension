<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation\Type;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sales\QuoteFactory as DotdigitalQuoteFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdateHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\AbandonedCart as AbandonedCartUpdater;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\AbandonedCartFactory as AbandonedCartUpdaterFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\Type\AbandonedCart;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Test\Unit\Traits\AutomationProcessorTrait;
use Magento\Newsletter\Model\Subscriber;
use Magento\Quote\Model\QuoteFactory;
use Magento\Newsletter\Model\SubscriberFactory;
use PHPUnit\Framework\TestCase;

/**
 * This test is essentially a further test for Sync\Automation\AutomationProcessor,
 * which AbandonedCart extends.
 */
class AbandonedCartTest extends TestCase
{
    use AutomationProcessorTrait;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var ContactResponseHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactResponseHandlerMock;

    /**
     * @var Automation|\PHPUnit\Framework\MockObject\MockObject
     */
    private $automationModelMock;

    /**
     * @var AutomationResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $automationResourceMock;

    /**
     * @var ContactCollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var DataFieldUpdateHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFieldUpdateHandlerMock;

    /**
     * @var AbandonedCartUpdater|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFieldUpdaterMock;

    /**
     * @var AbandonedCartUpdaterFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFieldUpdaterFactoryMock;

    /**
     * @var DotdigitalQuoteFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ddgQuoteFactoryMock;

    /**
     * @var QuoteFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteFactoryMock;

    /**
     * @var SubscriberFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subscriberFactoryMock;

    /**
     * @var Subscriber|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subscriberModelMock;

    /**
     * @var AbandonedCart
     */
    private $abandonedCart;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->contactResponseHandlerMock = $this->createMock(ContactResponseHandler::class);
        $this->automationResourceMock = $this->createMock(AutomationResource::class);
        $this->contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->dataFieldUpdateHandlerMock = $this->createMock(DataFieldUpdateHandler::class);
        $this->dataFieldUpdaterMock = $this->createMock(AbandonedCartUpdater::class);
        $this->dataFieldUpdaterFactoryMock = $this->createMock(AbandonedCartUpdaterFactory::class);
        $this->ddgQuoteFactoryMock = $this->createMock(DotdigitalQuoteFactory::class);
        $this->quoteFactoryMock = $this->createMock(QuoteFactory::class);
        $this->subscriberFactoryMock = $this->createMock(SubscriberFactory::class);
        $this->subscriberModelMock = $this->createMock(Subscriber::class);
        $this->contactModelMock = $this->getMockBuilder(Contact::class)
            ->addMethods(['getCustomerId', 'getIsGuest'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->automationModelMock = $this->getMockBuilder(Automation::class)
            ->addMethods(['getEmail', 'getWebsiteId', 'getAutomationType'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->abandonedCart = new AbandonedCart(
            $this->helperMock,
            $this->loggerMock,
            $this->automationResourceMock,
            $this->contactCollectionFactoryMock,
            $this->dataFieldUpdateHandlerMock,
            $this->contactResponseHandlerMock,
            $this->subscriberFactoryMock,
            $this->dataFieldUpdaterFactoryMock,
            $this->ddgQuoteFactoryMock,
            $this->quoteFactoryMock
        );
    }

    public function testThatWeCompleteProcessLoopIfQuoteHasItems()
    {
        $contact = $this->getSubscribedContact();
        $quoteModelMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quoteItemModelMock = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $ddgQuoteModelMock = $this->createMock(\Dotdigitalgroup\Email\Model\Sales\Quote::class);

        $this->setupAutomationModel();
        $this->setupContactModel();

        $this->helperMock->expects($this->once())
            ->method('getOrCreateContact')
            ->willReturn($contact);

        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteModelMock);

        $quoteModelMock->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->willReturn($quoteModelMock);

        $quoteModelMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$quoteItemModelMock]);

        $this->automationResourceMock->expects($this->never())
            ->method('setStatusAndSaveAutomation');

        $this->subscriberFactoryMock
            ->method('create')
            ->willReturn($this->subscriberModelMock);

        $this->dataFieldUpdaterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->dataFieldUpdaterMock);

        $this->dataFieldUpdaterMock->expects($this->once())
            ->method('setDatafields')
            ->willReturn($this->dataFieldUpdaterMock);

        $this->dataFieldUpdaterMock->expects($this->once())
            ->method('updateDatafields');

        $this->ddgQuoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($ddgQuoteModelMock);

        $ddgQuoteModelMock->expects($this->once())
            ->method('getMostExpensiveItem');

        $this->abandonedCart->process($this->getAutomationCollectionMock());
    }

    public function testThatWeExitProcessLoopIfQuoteHasNoItems()
    {
        $contact = $this->getSubscribedContact();
        $quoteModelMock = $this->createMock(\Magento\Quote\Model\Quote::class);

        $this->setupAutomationModel();
        $this->setupContactModel();

        $this->helperMock->expects($this->once())
            ->method('getOrCreateContact')
            ->willReturn($contact);

        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteModelMock);

        $quoteModelMock->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->willReturn($quoteModelMock);

        $quoteModelMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([]);

        $this->automationResourceMock->expects($this->once())
            ->method('setStatusAndSaveAutomation')
            ->with(
                $this->automationModelMock,
                StatusInterface::CANCELLED
            );

        $this->dataFieldUpdaterMock->expects($this->never())
            ->method('setDatafields');

        $this->abandonedCart->process($this->getAutomationCollectionMock());
    }
}
