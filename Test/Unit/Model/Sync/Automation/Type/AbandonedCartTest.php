<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation\Type;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\Sales\QuoteFactory as DotdigitalQuoteFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\ContactManager;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldTypeHandler;
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
     * @var ContactFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactFactoryMock;

    /**
     * @var ContactManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactManagerMock;

    /**
     * @var DataFieldCollector|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFieldCollectorMock;

    /**
     * @var DataFieldTypeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFieldTypeHandlerMock;

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

    /**
     * @var Contact&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactModelMock;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->contactResponseHandlerMock = $this->createMock(ContactResponseHandler::class);
        $this->automationResourceMock = $this->createMock(AutomationResource::class);
        $this->contactFactoryMock = $this->createMock(ContactFactory::class);
        $this->contactManagerMock = $this->createMock(ContactManager::class);
        $this->dataFieldCollectorMock = $this->createMock(DataFieldCollector::class);
        $this->dataFieldTypeHandlerMock = $this->createMock(DataFieldTypeHandler::class);
        $this->dataFieldUpdaterMock = $this->createMock(AbandonedCartUpdater::class);
        $this->dataFieldUpdaterFactoryMock = $this->createMock(AbandonedCartUpdaterFactory::class);
        $this->ddgQuoteFactoryMock = $this->createMock(DotdigitalQuoteFactory::class);
        $this->quoteFactoryMock = $this->createMock(QuoteFactory::class);
        $this->subscriberFactoryMock = $this->createMock(SubscriberFactory::class);
        $this->subscriberModelMock = $this->createMock(Subscriber::class);
        $this->contactModelMock = $this->getMockBuilder(Contact::class)
            ->onlyMethods(['loadByCustomerEmail'])
            ->addMethods(['getCustomerId', 'getIsGuest'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->automationModelMock = $this->getMockBuilder(Automation::class)
            ->addMethods(['getEmail', 'getWebsiteId', 'getStoreId', 'getAutomationType'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->abandonedCart = new AbandonedCart(
            $this->helperMock,
            $this->loggerMock,
            $this->automationResourceMock,
            $this->contactFactoryMock,
            $this->contactManagerMock,
            $this->dataFieldCollectorMock,
            $this->dataFieldTypeHandlerMock,
            $this->contactResponseHandlerMock,
            $this->subscriberFactoryMock,
            $this->dataFieldUpdaterFactoryMock,
            $this->ddgQuoteFactoryMock,
            $this->quoteFactoryMock
        );
    }

    public function testThatWeCompleteProcessLoopIfQuoteHasItems()
    {
        $quoteModelMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quoteItemModelMock = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $ddgQuoteModelMock = $this->createMock(\Dotdigitalgroup\Email\Model\Sales\Quote::class);

        $this->setupAutomationModel();
        $this->setupContactModel();
        $this->setupSubscriberModel();

        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteModelMock);

        $quoteModelMock->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->willReturn($quoteModelMock);

        $quoteModelMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$quoteItemModelMock]);

        $this->dataFieldUpdaterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->dataFieldUpdaterMock);

        $this->dataFieldUpdaterMock->expects($this->once())
            ->method('setDatafields')
            ->willReturn($this->dataFieldUpdaterMock);

        $this->dataFieldUpdaterMock->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $this->ddgQuoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($ddgQuoteModelMock);

        $ddgQuoteModelMock->expects($this->once())
            ->method('getMostExpensiveItem');

        $this->automationResourceMock->expects($this->never())
            ->method('setStatusAndSaveAutomation');

        $this->abandonedCart->process($this->getAutomationCollectionMock());
    }

    public function testThatWeExitProcessLoopIfQuoteHasNoItems()
    {
        $quoteModelMock = $this->createMock(\Magento\Quote\Model\Quote::class);

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

        $this->abandonedCart->process($this->getAutomationCollectionMock());
    }
}
