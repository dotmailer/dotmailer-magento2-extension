<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessor;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldUpdateHandler;
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
     * @var ContactResponseHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactResponseHandlerMock;

    /**
     * @var AutomationResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $automationResourceMock;

    /**
     * @var ContactCollection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactCollectionMock;

    /**
     * @var CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var DataFieldUpdateHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataFieldUpdateHandlerMock;

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
        $this->contactResponseHandlerMock = $this->createMock(ContactResponseHandler::class);
        $this->automationResourceMock = $this->createMock(AutomationResource::class);
        $this->contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->dataFieldUpdateHandlerMock = $this->createMock(DataFieldUpdateHandler::class);
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

        $this->automationProcessor = new AutomationProcessor(
            $this->helperMock,
            $this->contactResponseHandlerMock,
            $this->automationResourceMock,
            $this->contactCollectionFactoryMock,
            $this->dataFieldUpdateHandlerMock,
            $this->subscriberFactoryMock
        );
    }

    public function testEmailIsPushedToCustomerAddressBook()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();

        $this->helperMock->expects($this->once())
            ->method('getCustomerAddressBook')
            ->willReturn('123456');

        $this->contactModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn('10');

        $clientMock = $this->createMock(Client::class);
        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->willReturn($clientMock);

        $clientMock->expects($this->once())
            ->method('postAddressBookContacts');

        $this->helperMock->expects($this->never())
            ->method('getOrCreateContact');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testGuestContactIsPushedToGuestAddressBook()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();

        $this->helperMock->expects($this->once())
            ->method('getCustomerAddressBook')
            ->willReturn('123456');

        $this->helperMock->expects($this->once())
            ->method('getGuestAddressBook')
            ->willReturn('78999');

        $this->contactModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(0);

        $this->contactModelMock->expects($this->once())
            ->method('getIsGuest')
            ->willReturn(1);

        $clientMock = $this->createMock(Client::class);
        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->willReturn($clientMock);

        $clientMock->expects($this->once())
            ->method('postAddressBookContacts');

        $this->helperMock->expects($this->never())
            ->method('getOrCreateContact');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testSubscribedContactIsPushedToSubscriberAddressBook()
    {
        $contact = $this->getSubscribedContact();
        $this->setupAutomationModel();
        $this->setupContactModel();

        $this->helperMock->expects($this->once())
            ->method('getSubscriberAddressBook')
            ->willReturn('123456');

        $this->helperMock->expects($this->once())
            ->method('getOrCreateContact')
            ->willReturn($contact);

        $this->subscriberFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->subscriberModelMock);

        $this->subscriberModelMock->expects($this->once())
            ->method('loadBySubscriberEmail')
            ->willReturn($this->subscriberModelMock);

        $this->subscriberModelMock->expects($this->once())
            ->method('getId')
            ->willReturn(5);

        $clientMock = $this->createMock(Client::class);
        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->willReturn($clientMock);

        $clientMock->expects($this->once())
            ->method('postAddressBookContacts');

        $this->automationModelMock->expects($this->once())
            ->method('getAutomationType')
            ->willReturn('customer_automation');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testEmailIsNotPushedToAnyAddressBookIfNoAddressBookIsMapped()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();

        $this->helperMock->expects($this->once())
            ->method('getCustomerAddressBook')
            ->willReturn(null);

        $this->contactModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn('10');

        $clientMock = $this->createMock(Client::class);
        $this->helperMock->expects($this->never())
            ->method('getWebsiteApiClient');

        $clientMock->expects($this->never())
            ->method('postAddressBookContacts');

        $this->helperMock->expects($this->once())
            ->method('getOrCreateContact');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testSubscribedDotdigitalContactTriggersDataFieldUpdate()
    {
        $contact = $this->getSubscribedContact();
        $this->setupAutomationModel();
        $this->setupContactModel();

        $this->helperMock->expects($this->once())
            ->method('getOrCreateContact')
            ->willReturn($contact);

        $this->automationResourceMock->expects($this->never())
            ->method('setStatusAndSaveAutomation');

        $this->automationModelMock->expects($this->once())
            ->method('getAutomationType')
            ->willReturn('customer_automation');

        $this->subscriberFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->subscriberModelMock);

        $this->dataFieldUpdateHandlerMock->expects($this->once())
            ->method('updateDatafieldsByType');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testPendingOptInDotdigitalContactIsSavedNotProcessed()
    {
        $contact = $this->getPendingOptInContact();
        $this->setupAutomationModel();
        $this->setupContactModel();

        $this->helperMock->expects($this->once())
            ->method('getOrCreateContact')
            ->willReturn($contact);

        $this->automationResourceMock->expects($this->once())
            ->method('setStatusAndSaveAutomation');

        $this->subscriberFactoryMock->expects($this->never())
            ->method('create');

        $this->dataFieldUpdateHandlerMock->expects($this->never())
            ->method('updateDatafieldsByType');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }

    public function testRowIsMarkedAsFailedIfContactNotFound()
    {
        $this->setupAutomationModel();
        $this->setupContactModel();

        $this->helperMock->expects($this->once())
            ->method('getOrCreateContact')
            ->willReturn(false);

        $this->automationResourceMock->expects($this->once())
            ->method('setStatusAndSaveAutomation')
            ->with(
                $this->automationModelMock,
                StatusInterface::FAILED,
                'Contact cannot be created or has been suppressed'
            );

        $this->dataFieldUpdateHandlerMock->expects($this->never())
            ->method('updateDatafieldsByType');

        $this->automationProcessor->process($this->getAutomationCollectionMock());
    }
}
