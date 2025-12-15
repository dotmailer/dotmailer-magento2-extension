<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation\Type;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\Newsletter\BackportedSubscriberLoader;
use Dotdigitalgroup\Email\Model\Newsletter\OptInTypeFinder;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Sales\QuoteFactory as DotdigitalQuoteFactory;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\ContactManager;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\AbandonedCart as AbandonedCartUpdater;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\Updater\AbandonedCartFactory as AbandonedCartUpdaterFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\OrderManager;
use Dotdigitalgroup\Email\Model\Sync\Automation\Type\AbandonedCart;
use Dotdigitalgroup\Email\Test\Unit\Traits\AutomationProcessorTrait;
use Magento\Newsletter\Model\Subscriber;
use Magento\Quote\Model\QuoteFactory;
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
     * @var ContactManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactManagerMock;

    /**
     * @var OrderManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderManagerMock;

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
     * @var BackportedSubscriberLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $backportedSubscriberLoaderMock;

    /**
     * @var OptInTypeFinder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $optInTypeFinderMock;

    /**
     * @var AbandonedCart
     */
    private $abandonedCart;

    /**
     * @var Contact&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contactModelMock;

    /**
     * @var Subscriber|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subscriberModelMock;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->contactResponseHandlerMock = $this->createMock(ContactResponseHandler::class);
        $this->automationResourceMock = $this->createMock(AutomationResource::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->contactManagerMock = $this->createMock(ContactManager::class);
        $this->orderManagerMock = $this->createMock(OrderManager::class);
        $this->dataFieldTypeHandlerMock = $this->createMock(DataFieldTypeHandler::class);
        $this->dataFieldUpdaterMock = $this->createMock(AbandonedCartUpdater::class);
        $this->dataFieldUpdaterFactoryMock = $this->createMock(AbandonedCartUpdaterFactory::class);
        $this->ddgQuoteFactoryMock = $this->createMock(DotdigitalQuoteFactory::class);
        $this->quoteFactoryMock = $this->createMock(QuoteFactory::class);
        $this->backportedSubscriberLoaderMock = $this->createMock(BackportedSubscriberLoader::class);
        $this->optInTypeFinderMock = $this->createMock(OptInTypeFinder::class);
        $this->contactModelMock = $this->getMockBuilder(Contact::class)
            ->onlyMethods(['loadByCustomerEmail'])
            ->addMethods(['getCustomerId', 'getIsGuest'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->automationModelMock = $this->getMockBuilder(Automation::class)
            ->addMethods(['getEmail', 'getWebsiteId', 'getStoreId', 'getAutomationType'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->subscriberModelMock = $this->createMock(Subscriber::class);

        $this->abandonedCart = new AbandonedCart(
            $this->helperMock,
            $this->loggerMock,
            $this->optInTypeFinderMock,
            $this->automationResourceMock,
            $this->contactCollectionFactoryMock,
            $this->contactManagerMock,
            $this->orderManagerMock,
            $this->dataFieldTypeHandlerMock,
            $this->backportedSubscriberLoaderMock,
            $this->dataFieldUpdaterFactoryMock,
            $this->ddgQuoteFactoryMock,
            $this->quoteFactoryMock
        );
    }

    public function testThatWeCompleteProcessLoopIfQuoteHasItems()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();
        $this->setupSubscriberModel();
        $this->setupACRelatedModels();

        $this->automationModelMock->expects($this->once())
            ->method('getAutomationType')
            ->willReturn(AutomationTypeHandler::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT);

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

    public function testACEnrolmentSucceedsViaACLoophole()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();
        $this->setupSubscriberModel();
        $this->setupACRelatedModels();

        $this->automationModelMock->expects($this->once())
            ->method('getAutomationType')
            ->willReturn(AutomationTypeHandler::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT);

        $this->subscriberModelMock->expects($this->once())
            ->method('isSubscribed')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('isOnlySubscribersForContactSync')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('isOnlySubscribersForAC')
            ->willReturn(false);

        $this->contactManagerMock->expects($this->once())
            ->method('prepareDotdigitalContact');

        $this->automationResourceMock->expects($this->never())
            ->method('setStatusAndSaveAutomation');

        $this->abandonedCart->process($this->getAutomationCollectionMock());
    }

    public function testACEnrolmentFailsIfACLoopholeClosed()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();
        $this->setupSubscriberModel();

        $quoteModelMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quoteItemModelMock = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $ddgQuoteModelMock = $this->createMock(\Dotdigitalgroup\Email\Model\Sales\Quote::class);

        $this->quoteFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($quoteModelMock);

        $quoteModelMock->expects($this->once())
            ->method('loadByIdWithoutStore')
            ->willReturn($quoteModelMock);

        $quoteModelMock->expects($this->once())
            ->method('getAllItems')
            ->willReturn([$quoteItemModelMock]);

        $this->subscriberModelMock->expects($this->once())
            ->method('isSubscribed')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('isOnlySubscribersForContactSync')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('isOnlySubscribersForAC')
            ->willReturn(true);

        $this->dataFieldUpdaterMock->expects($this->never())
            ->method('setDatafields');

        $this->contactManagerMock->expects($this->never())
            ->method('prepareDotdigitalContact');

        $this->automationResourceMock->expects($this->once())
            ->method('setStatusAndSaveAutomation');

        $this->abandonedCart->process($this->getAutomationCollectionMock());
    }

    private function setupACRelatedModels()
    {
        $quoteModelMock = $this->createMock(\Magento\Quote\Model\Quote::class);
        $quoteItemModelMock = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $ddgQuoteModelMock = $this->createMock(\Dotdigitalgroup\Email\Model\Sales\Quote::class);

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
    }
}
