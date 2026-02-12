<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation;

use Dotdigital\V3\Models\Contact as ContactModel;
use Dotdigital\V3\Models\ContactFactory as DotdigitalContactFactory;
use Dotdigital\V3\Resources\Contacts;
use Dotdigitalgroup\Email\Exception\PendingOptInException;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\StatusInterface;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\ContactManager;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SingleSubscriberSyncer;
use Dotdigitalgroup\Email\Test\Unit\Traits\AutomationProcessorTrait;
use Dotdigitalgroup\Email\Test\Unit\Traits\SdkTestDoublesTrait;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContactManagerTest extends TestCase
{
    use AutomationProcessorTrait;
    use SdkTestDoublesTrait;

    /**
     * @var DotdigitalContactFactory|MockObject
     */
    private $sdkContactFactoryMock;

    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var Client|MockObject
     */
    private $v3ClientMock;

    /**
     * @var ContactResponseHandler|MockObject
     */
    private $contactResponseHandlerMock;

    /**
     * @var ContactResource|MockObject
     */
    private $contactResourceMock;

    /**
     * @var AutomationResource|MockObject
     */
    private $automationResourceMock;

    /**
     * @var ContactCollection|MockObject
     */
    private $contactCollectionMock;

    /**
     * @var CollectionFactory|MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var ContactManager|MockObject
     */
    private $contactManagerMock;

    /**
     * @var DataFieldCollector|MockObject
     */
    private $dataFieldCollectorMock;

    /**
     * @var DataFieldTypeHandler|MockObject
     */
    private $dataFieldTypeHandlerMock;

    /**
     * @var SingleSubscriberSyncer
     */
    private $singleSubscriberSyncerMock;

    /**
     * @var SubscriberFactory|MockObject
     */
    private $subscriberFactoryMock;

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

    /**
     * @var ContactManager
     */
    private $contactManager;

    protected function setUp() :void
    {
        $this->sdkContactFactoryMock = $this->createMock(DotdigitalContactFactory::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->v3ClientMock = $this->createMock(Client::class);
        $this->contactResponseHandlerMock = $this->createMock(ContactResponseHandler::class);
        $this->contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactResourceMock = $this->createMock(ContactResource::class);
        $this->contactCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->contactManagerMock = $this->createMock(ContactManager::class);
        $this->dataFieldCollectorMock = $this->createMock(DataFieldCollector::class);
        $this->dataFieldTypeHandlerMock = $this->createMock(DataFieldTypeHandler::class);
        $this->singleSubscriberSyncerMock = $this->createMock(SingleSubscriberSyncer::class);
        $this->subscriberFactoryMock = $this->createMock(SubscriberFactory::class);
        $this->subscriberModelMock = $this->createMock(Subscriber::class);
        $this->contactModelMock = $this->getMockBuilder(Contact::class)
            ->onlyMethods(['getId'])
            ->addMethods([
                'getEmail',
                'getWebsiteId',
                'getCustomerId',
                'getIsGuest',
                'setSubscriberImported',
                'setEmailImported'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->automationModelMock = $this->getMockBuilder(Automation::class)
            ->addMethods(['getEmail', 'getWebsiteId', 'getAutomationType'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactManager = new ContactManager(
            $this->sdkContactFactoryMock,
            $this->helperMock,
            $this->clientFactoryMock,
            $this->contactResourceMock,
            $this->dataFieldCollectorMock,
            $this->singleSubscriberSyncerMock
        );
    }

    public function testGenericPrepareDDContact()
    {
        $contactId = 123456;
        $email = 'chaz@emailsim.io';

        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactModelMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn('1');

        $sdkContact = $this->createMock(ContactModel::class);
        $this->sdkContactFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sdkContact);

        $sdkContact->expects($this->once())->method('setMatchIdentifier')
            ->with('email');
        $sdkContact->expects($this->once())->method('setIdentifiers')
            ->with(['email' => $email]);

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => 1]])
            ->willReturn($this->v3ClientMock);

        // Create a concrete test double contact to work around deprecation of addMethods() in PHPUnit
        $responseContact = $this->createContactModelWithChannelProperties(
            $contactId,
            StatusInterface::SUBSCRIBED
        );

        $contactsResourceMock = $this->createMock(Contacts::class);
        $this->v3ClientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);
        $contactsResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->with($email, $sdkContact)
            ->willReturn($responseContact);

        $returnedId = $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields(),
            AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER
        );

        $this->assertEquals($contactId, $returnedId);
    }

    public function testEmailIsPushedToCustomerAddressBook()
    {
        $email = 'chaz@emailsim.io';
        $contactId = 123456;

        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactModelMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->contactModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn('10');

        $this->helperMock->expects($this->once())
            ->method('isCustomerSyncEnabled')
            ->willReturn(true);

        $this->subscriberModelMock->expects($this->any())
            ->method('isSubscribed')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('getCustomerAddressBook')
            ->willReturn('9856');

        $this->dataFieldCollectorMock->expects($this->once())
            ->method('collectForCustomer')
            ->willReturn($this->getDummyCustomerDataFields());

        // Setup V3 client mocks
        $sdkContact = $this->createMock(ContactModel::class);
        $this->sdkContactFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sdkContact);

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => 1]])
            ->willReturn($this->v3ClientMock);

        $responseContact = $this->createContactModelWithChannelProperties(
            $contactId,
            StatusInterface::SUBSCRIBED
        );

        $contactsResourceMock = $this->createMock(Contacts::class);
        $this->v3ClientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);
        $contactsResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->willReturn($responseContact);

        $this->contactModelMock->expects($this->once())
            ->method('setEmailImported');

        $this->contactResourceMock->expects($this->once())
            ->method('save');

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields(),
            AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER
        );
    }

    public function testEmailIsPushedToGuestAddressBook()
    {
        $email = 'chaz@emailsim.io';
        $contactId = 123456;

        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactModelMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->contactModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn(0);

        $this->contactModelMock->expects($this->once())
            ->method('getIsGuest')
            ->willReturn(1);

        $this->helperMock->expects($this->once())
            ->method('isGuestSyncEnabled')
            ->willReturn(true);

        $this->subscriberModelMock->expects($this->any())
            ->method('isSubscribed')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('getGuestAddressBook')
            ->willReturn('9541');

        $this->dataFieldCollectorMock->expects($this->once())
            ->method('collectForGuest')
            ->willReturn($this->getDummyGuestDataFields());

        $sdkContact = $this->createMock(ContactModel::class);
        $this->sdkContactFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sdkContact);

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => 1]])
            ->willReturn($this->v3ClientMock);

        $responseContact = $this->createContactModelWithChannelProperties(
            $contactId,
            StatusInterface::SUBSCRIBED
        );

        $contactsResourceMock = $this->createMock(Contacts::class);
        $this->v3ClientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);
        $contactsResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->willReturn($responseContact);

        $this->contactModelMock->expects($this->once())
            ->method('setEmailImported');

        $this->contactResourceMock->expects($this->once())
            ->method('save');

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields(),
            AutomationTypeHandler::AUTOMATION_TYPE_NEW_GUEST_ORDER
        );
    }

    public function testSubscribedContactIsPushedToSubscriberAddressBook()
    {
        $email = 'chaz@emailsim.io';
        $contactId = 5;

        $this->contactModelMock->expects($this->any())
            ->method('getId')
            ->willReturn($contactId);

        $this->contactModelMock->expects($this->any())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactModelMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->subscriberModelMock->expects($this->once())
            ->method('isSubscribed')
            ->willReturn(true);

        // Setup V3 client mocks
        $sdkContact = $this->createMock(ContactModel::class);
        $this->sdkContactFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sdkContact);

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => 1]])
            ->willReturn($this->v3ClientMock);

        $responseContact = $this->createContactModelWithChannelProperties(
            123456,
            StatusInterface::SUBSCRIBED
        );

        $contactsResourceMock = $this->createMock(Contacts::class);
        $this->v3ClientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);
        $contactsResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->willReturn($responseContact);

        $this->singleSubscriberSyncerMock->expects($this->once())
            ->method('execute')
            ->with($this->contactModelMock);

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields(),
            AutomationTypeHandler::AUTOMATION_TYPE_NEW_SUBSCRIBER
        );
    }

    public function testEmailIsPushedEvenIfExportFails()
    {
        $email = 'chaz@emailsim.io';
        $contactId = 123456;

        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactModelMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->contactModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn('10');

        $this->helperMock->expects($this->once())
            ->method('isCustomerSyncEnabled')
            ->willReturn(true);

        $this->subscriberModelMock->expects($this->any())
            ->method('isSubscribed')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('getCustomerAddressBook')
            ->willReturn('9856');

        $this->dataFieldCollectorMock->expects($this->once())
            ->method('collectForCustomer')
            ->willReturn([]);

        // Setup V3 client mocks
        $sdkContact = $this->createMock(ContactModel::class);
        $this->sdkContactFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sdkContact);

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => 1]])
            ->willReturn($this->v3ClientMock);

        $responseContact = $this->createContactModelWithChannelProperties(
            $contactId,
            StatusInterface::SUBSCRIBED
        );

        $contactsResourceMock = $this->createMock(Contacts::class);
        $this->v3ClientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);
        $contactsResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->willReturn($responseContact);

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields(),
            AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER
        );
    }

    public function testEmailIsNotPushedToAnyAddressBookIfNoAddressBookIsMapped()
    {
        $email = 'chaz@emailsim.io';
        $contactId = 123456;

        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactModelMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->contactModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn('10');

        $this->subscriberModelMock->expects($this->any())
            ->method('isSubscribed')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('isCustomerSyncEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('getCustomerAddressBook')
            ->willReturn(0);

        $this->dataFieldCollectorMock->expects($this->once())
            ->method('collectForCustomer')
            ->willReturn($this->getDummyCustomerDataFields());

        $sdkContact = $this->createMock(ContactModel::class);
        $this->sdkContactFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sdkContact);

        $sdkContact->expects($this->never())
            ->method('setLists');

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => 1]])
            ->willReturn($this->v3ClientMock);

        $responseContact = $this->createContactModelWithChannelProperties(
            $contactId,
            StatusInterface::SUBSCRIBED
        );

        $contactsResourceMock = $this->createMock(Contacts::class);
        $this->v3ClientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);
        $contactsResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->willReturn($responseContact);

        $this->contactModelMock->expects($this->never())
            ->method('setEmailImported');

        $this->contactResourceMock->expects($this->never())
            ->method('save');

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields(),
            AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER
        );
    }

    public function testContactNotPushedToListsIfEnrollingViaAcLoophole()
    {
        $email = 'chaz@emailsim.io';
        $contactId = 123456;

        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactModelMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->contactModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn('10');

        $this->helperMock->expects($this->once())
            ->method('isCustomerSyncEnabled')
            ->willReturn(true);

        $this->subscriberModelMock->expects($this->any())
            ->method('isSubscribed')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('isOnlySubscribersForContactSync')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('isOnlySubscribersForAC')
            ->willReturn(false);

        // Setup V3 client mocks
        $sdkContact = $this->createMock(ContactModel::class);
        $this->sdkContactFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sdkContact);

        $sdkContact->expects($this->never())
            ->method('setLists');

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => 1]])
            ->willReturn($this->v3ClientMock);

        $responseContact = $this->createContactModelWithChannelProperties(
            $contactId,
            StatusInterface::SUBSCRIBED
        );

        $contactsResourceMock = $this->createMock(Contacts::class);
        $this->v3ClientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);
        $contactsResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->willReturn($responseContact);

        $this->contactModelMock->expects($this->never())
            ->method('setEmailImported');

        $this->contactResourceMock->expects($this->never())
            ->method('save');

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields(),
            AutomationTypeHandler::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT
        );
    }

    public function testExceptionThrownIfContactStatusIsPendingOptIn()
    {
        $email = 'chaz@emailsim.io';
        $contactId = 123456;

        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn($email);

        $this->contactModelMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn('1');

        $sdkContact = $this->createMock(ContactModel::class);
        $this->sdkContactFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sdkContact);

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => 1]])
            ->willReturn($this->v3ClientMock);

        $responseContact = $this->createContactModelWithChannelProperties(
            $contactId,
            StatusInterface::PENDING_OPT_IN
        );

        $contactsResourceMock = $this->createMock(Contacts::class);
        $this->v3ClientMock->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);
        $contactsResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->willReturn($responseContact);

        $this->expectException(PendingOptInException::class);

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields(),
            AutomationTypeHandler::AUTOMATION_TYPE_NEW_SUBSCRIBER
        );
    }

    private function getDefaultDataFields()
    {
        return [
            [
                'Key' => 'STORE_NAME',
                'Value' => 'Chaz store',
            ],
            [
                'Key' => 'WEBSITE_NAME',
                'Value' => 'Chaz website',
            ]
        ];
    }

    private function getDummyCustomerDataFields()
    {
        return [
            [
                'Key' => 'FIRST_NAME',
                'Value' => 'Chaz',
            ],
            [
                'Key' => 'LAST_NAME',
                'Value' => 'Kangaroo',
            ],
            [
                'Key' => 'SUBSCRIBER_STATUS',
                'Value' => 'Subscribed',
            ]
        ];
    }

    private function getDummyGuestDataFields()
    {
        return [
            [
                'Key' => 'STORE_NAME',
                'Value' => 'Chaz store',
            ],
            [
                'Key' => 'STORE_VIEW_NAME',
                'Value' => 'Chaz store view',
            ],
            [
                'Key' => 'WEBSITE_NAME',
                'Value' => 'Chaz website',
            ]
        ];
    }
}
