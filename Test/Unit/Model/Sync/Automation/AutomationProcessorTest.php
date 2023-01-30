<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation;

use Dotdigitalgroup\Email\Exception\PendingOptInException;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessor;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\ContactManager;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldTypeHandler;
use Dotdigitalgroup\Email\Test\Unit\Traits\AutomationProcessorTrait;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
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
     * @var SubscriberFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subscriberFactoryMock;

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
        $this->contactResponseHandlerMock = $this->createMock(ContactResponseHandler::class);
        $this->automationResourceMock = $this->createMock(AutomationResource::class);
        $this->contactFactoryMock = $this->createMock(ContactFactory::class);
        $this->contactManagerMock = $this->createMock(ContactManager::class);
        $this->dataFieldCollectorMock = $this->createMock(DataFieldCollector::class);
        $this->dataFieldTypeHandlerMock = $this->createMock(DataFieldTypeHandler::class);
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

        $this->automationProcessor = new AutomationProcessor(
            $this->helperMock,
            $this->loggerMock,
            $this->contactResponseHandlerMock,
            $this->automationResourceMock,
            $this->contactFactoryMock,
            $this->contactManagerMock,
            $this->dataFieldCollectorMock,
            $this->dataFieldTypeHandlerMock,
            $this->subscriberFactoryMock
        );
    }

    public function testAutomationIsProcessedForNewCustomer()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();
        $this->setupSubscriberModel();

        $this->automationModelMock->expects($this->once())
            ->method('getAutomationType')
            ->willReturn(AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER);

        $this->dataFieldTypeHandlerMock->expects($this->once())
            ->method('retrieveDatafieldsByType');

        $this->contactManagerMock->expects($this->once())
            ->method('prepareDotdigitalContact')
            ->willReturn(123456);

        $this->automationResourceMock->expects($this->never())
            ->method('setStatusAndSaveAutomation');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testAutomationIsSavedIfContactIsPendingOptIn()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();
        $this->setupSubscriberModel();

        $this->automationModelMock->expects($this->once())
            ->method('getAutomationType')
            ->willReturn(AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER);

        $this->dataFieldTypeHandlerMock->expects($this->once())
            ->method('retrieveDatafieldsByType');

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

        $this->automationModelMock->expects($this->once())
            ->method('getAutomationType')
            ->willReturn(AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER);

        $this->dataFieldTypeHandlerMock->expects($this->once())
            ->method('retrieveDatafieldsByType');

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
