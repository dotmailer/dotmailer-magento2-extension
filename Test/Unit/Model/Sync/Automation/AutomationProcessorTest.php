<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation;

use Dotdigital\V3\Models\Contact\ChannelProperties\EmailChannelProperties\OptInTypeInterface;
use Dotdigitalgroup\Email\Exception\PendingOptInException;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\Newsletter\BackportedSubscriberLoader;
use Dotdigitalgroup\Email\Model\Newsletter\OptInTypeFinder;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessor;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\ContactManager;
use Dotdigitalgroup\Email\Model\Sync\Automation\OrderManager;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldTypeHandler;
use Dotdigitalgroup\Email\Test\Unit\Traits\AutomationProcessorTrait;
use Magento\Newsletter\Model\Subscriber;
use PHPUnit\Framework\TestCase;

class AutomationProcessorTest extends TestCase
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
     * @var BackportedSubscriberLoader|\PHPUnit_Framework_MockObject_MockObject
     */
    private $backportedSubscriberLoaderMock;

    /**
     * @var OptInTypeFinder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $optInTypeFinderMock;

    /**
     * @var AutomationProcessor
     */
    private $automationProcessor;

    /**
     * @var Automation|\PHPUnit\Framework\MockObject\MockObject
     */
    private $automationModelMock;

    /**
     * @var Contact|\PHPUnit\Framework\MockObject\MockObject
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
        $this->automationResourceMock = $this->createMock(AutomationResource::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->contactManagerMock = $this->createMock(ContactManager::class);
        $this->orderManagerMock = $this->createMock(OrderManager::class);
        $this->dataFieldTypeHandlerMock = $this->createMock(DataFieldTypeHandler::class);
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

        $this->automationProcessor = new AutomationProcessor(
            $this->helperMock,
            $this->loggerMock,
            $this->optInTypeFinderMock,
            $this->automationResourceMock,
            $this->contactCollectionFactoryMock,
            $this->contactManagerMock,
            $this->orderManagerMock,
            $this->dataFieldTypeHandlerMock,
            $this->backportedSubscriberLoaderMock
        );
    }

    public function testAutomationIsProcessedForNewCustomer()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();
        $this->setupSubscriberModel();

        $this->automationModelMock->expects($this->exactly('2'))
            ->method('getAutomationType')
            ->willReturn(AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER);

        $this->dataFieldTypeHandlerMock->expects($this->once())
            ->method('retrieveDatafieldsByType');

        $this->optInTypeFinderMock->expects($this->once())
            ->method('getOptInType')
            ->willReturn(OptInTypeInterface::DOUBLE);

        $this->contactManagerMock->expects($this->once())
            ->method('prepareDotdigitalContact')
            ->willReturn(123456);

        $this->automationResourceMock->expects($this->never())
            ->method('setStatusAndSaveAutomation');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testAutomationFailsIfContactNotFound()
    {
        $this->setupAutomationModel();

        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->contactModelMock);

        $this->contactModelMock->expects($this->once())
            ->method('loadByCustomerEmail')
            ->willReturn(null);

        $this->dataFieldTypeHandlerMock->expects($this->never())
            ->method('retrieveDatafieldsByType');

        $this->optInTypeFinderMock->expects($this->never())
            ->method('getOptInType');

        $this->contactManagerMock->expects($this->never())
            ->method('prepareDotdigitalContact');

        $this->automationResourceMock->expects($this->once())
            ->method('setStatusAndSaveAutomation');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testAutomationFailsIfContactIsNotSubscribed()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();
        $this->setupSubscriberModel();

        $this->subscriberModelMock->expects($this->once())
            ->method('isSubscribed')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('isOnlySubscribersForContactSync')
            ->willReturn(true);

        $this->dataFieldTypeHandlerMock->expects($this->never())
            ->method('retrieveDatafieldsByType');

        $this->optInTypeFinderMock->expects($this->never())
            ->method('getOptInType');

        $this->contactManagerMock->expects($this->never())
            ->method('prepareDotdigitalContact');

        $this->automationResourceMock->expects($this->once())
            ->method('setStatusAndSaveAutomation');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testAutomationIsSavedIfContactIsPendingOptIn()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();
        $this->setupSubscriberModel();

        $this->automationModelMock->expects($this->exactly('2'))
            ->method('getAutomationType')
            ->willReturn(AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER);

        $this->dataFieldTypeHandlerMock->expects($this->once())
            ->method('retrieveDatafieldsByType');

        $this->optInTypeFinderMock->expects($this->once())
            ->method('getOptInType')
            ->willReturn(null);

        $this->contactManagerMock->expects($this->once())
            ->method('prepareDotdigitalContact')
            ->willThrowException(new PendingOptInException(__('Contact status is PendingOptIn, cannot be enrolled.')));

        $this->automationResourceMock->expects($this->once())
            ->method('setStatusAndSaveAutomation');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testAutomationIsMarkedAsFailedForAnyOtherException()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();
        $this->setupSubscriberModel();

        $this->automationModelMock->expects($this->exactly('2'))
            ->method('getAutomationType')
            ->willReturn(AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER);

        $this->dataFieldTypeHandlerMock->expects($this->once())
            ->method('retrieveDatafieldsByType');

        $this->optInTypeFinderMock->expects($this->once())
            ->method('getOptInType')
            ->willReturn(null);

        $this->contactManagerMock->expects($this->once())
            ->method('prepareDotdigitalContact')
            ->willThrowException(new \Exception(__('Something went wrong.')));

        $this->automationResourceMock->expects($this->once())
            ->method('setStatusAndSaveAutomation')
            ->with(
                $this->automationModelMock,
                StatusInterface::FAILED,
                'Something went wrong.'
            );

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }
}
