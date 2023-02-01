<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation;

use Dotdigitalgroup\Email\Exception\PendingOptInException;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Dotdigitalgroup\Email\Model\Sync\Automation\ContactManager;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldTypeHandler;
use Dotdigitalgroup\Email\Test\Unit\Traits\AutomationProcessorTrait;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use PHPUnit\Framework\TestCase;

class ContactManagerTest extends TestCase
{
    use AutomationProcessorTrait;

    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $clientMock;

    /**
     * @var ContactResponseHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactResponseHandlerMock;

    /**
     * @var ContactResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactResourceMock;

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
        $this->helperMock = $this->createMock(Data::class);
        $this->clientMock = $this->createMock(Client::class);
        $this->contactResponseHandlerMock = $this->createMock(ContactResponseHandler::class);
        $this->contactResourceMock = $this->createMock(ContactResource::class);
        $this->contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->contactManagerMock = $this->createMock(ContactManager::class);
        $this->dataFieldCollectorMock = $this->createMock(DataFieldCollector::class);
        $this->dataFieldTypeHandlerMock = $this->createMock(DataFieldTypeHandler::class);
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
            $this->helperMock,
            $this->contactResponseHandlerMock,
            $this->contactResourceMock,
            $this->dataFieldCollectorMock
        );
    }

    public function testGenericPrepareDDContact()
    {
        $contactId = 123456;

        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $this->contactModelMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->helperMock->expects($this->any())
            ->method('getWebsiteApiClient')
            ->willReturn($this->clientMock);

        $this->clientMock->expects($this->once())
            ->method('postContactWithConsentAndPreferences');

        $this->contactResponseHandlerMock->expects($this->once())
            ->method('getContactIdFromResponse')
            ->willReturn($contactId);

        $returnedId = $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields()
        );

        $this->assertEquals($contactId, $returnedId);
    }

    public function testEmailIsPushedToCustomerAddressBook()
    {
        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $this->contactModelMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->contactModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn('10');

        $this->helperMock->expects($this->once())
            ->method('isCustomerSyncEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('getCustomerAddressBook')
            ->willReturn('9856');

        $this->dataFieldCollectorMock->expects($this->once())
            ->method('collectForCustomer')
            ->willReturn($this->getDummyCustomerDataFields());

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->willReturn($this->clientMock);

        $this->clientMock->expects($this->once())
            ->method('addContactToAddressBook');

        $this->contactModelMock->expects($this->once())
            ->method('setEmailImported');

        $this->contactResourceMock->expects($this->once())
            ->method('save');

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields()
        );
    }

    public function testEmailIsPushedToGuestAddressBook()
    {
        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

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

        $this->helperMock->expects($this->once())
            ->method('getGuestAddressBook')
            ->willReturn('9541');

        $this->dataFieldCollectorMock->expects($this->once())
            ->method('collectForGuest')
            ->willReturn($this->getDummyGuestDataFields());

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->willReturn($this->clientMock);

        $this->clientMock->expects($this->once())
            ->method('addContactToAddressBook');

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields()
        );
    }

    public function testSubscribedContactIsPushedToSubscriberAddressBook()
    {
        $this->contactModelMock->expects($this->any())
            ->method('getId')
            ->willReturn('5');

        $this->contactModelMock->expects($this->any())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $this->contactModelMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->subscriberModelMock->expects($this->exactly(2))
            ->method('isSubscribed')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('isSubscriberSyncEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('getSubscriberAddressBook')
            ->willReturn('3886');

        $this->dataFieldCollectorMock->expects($this->once())
            ->method('collectForSubscriber')
            ->willReturn($this->getDummySubscriberDataFields());

        $this->dataFieldCollectorMock->expects($this->once())
            ->method('mergeFields')
            ->willReturn($this->getDummySubscriberDataFields());

        $this->dataFieldCollectorMock->expects($this->once())
            ->method('extractConsentFromPreparedDataFields')
            ->willReturn($this->getConsentDataFields());

        $this->helperMock->expects($this->exactly(2))
            ->method('getWebsiteApiClient')
            ->willReturn($this->clientMock);

        $this->clientMock->expects($this->once())
            ->method('postContactWithConsentAndPreferences');

        $this->clientMock->expects($this->once())
            ->method('updateContactWithConsentAndPreferences');

        $this->clientMock->expects($this->once())
            ->method('addContactToAddressBook');

        $this->contactModelMock->expects($this->once())
            ->method('setSubscriberImported');

        $this->contactResourceMock->expects($this->once())
            ->method('save');

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields()
        );
    }

    public function testEmailIsPushedEvenIfExportFails()
    {
        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $this->contactModelMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->contactModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn('10');

        $this->helperMock->expects($this->once())
            ->method('isCustomerSyncEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('getCustomerAddressBook')
            ->willReturn('9856');

        $this->dataFieldCollectorMock->expects($this->once())
            ->method('collectForCustomer')
            ->willReturn([]);

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->willReturn($this->clientMock);

        $this->clientMock->expects($this->once())
            ->method('addContactToAddressBook');

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields()
        );
    }

    public function testEmailIsNotPushedToAnyAddressBookIfNoAddressBookIsMapped()
    {
        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $this->contactModelMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->contactModelMock->expects($this->once())
            ->method('getCustomerId')
            ->willReturn('10');

        $this->helperMock->expects($this->once())
            ->method('isCustomerSyncEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->once())
            ->method('getCustomerAddressBook')
            ->willReturn(0);

        $this->dataFieldCollectorMock->expects($this->once())
            ->method('collectForCustomer')
            ->willReturn($this->getDummyCustomerDataFields());

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->willReturn($this->clientMock);

        $this->clientMock->expects($this->never())
            ->method('addContactToAddressBook');

        $this->contactModelMock->expects($this->never())
            ->method('setEmailImported');

        $this->contactResourceMock->expects($this->never())
            ->method('save');

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields()
        );
    }

    public function testExceptionThrownIfSubscriberNotSubscribedAndIsOnlySubscribersForSync()
    {
        $this->subscriberModelMock->expects($this->once())
            ->method('isSubscribed')
            ->willReturn(false);

        $this->helperMock->expects($this->once())
            ->method('isOnlySubscribersForContactSync')
            ->willReturn(true);

        $this->expectException(LocalizedException::class);

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields()
        );
    }

    public function testExceptionThrownIfContactStatusIsPendingOptIn()
    {
        $this->contactModelMock->expects($this->once())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $this->contactModelMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn('1');

        $this->helperMock->expects($this->any())
            ->method('getWebsiteApiClient')
            ->willReturn($this->clientMock);

        $this->clientMock->expects($this->once())
            ->method('postContactWithConsentAndPreferences');

        $this->contactResponseHandlerMock->expects($this->once())
            ->method('getStatusFromResponse')
            ->willReturn(StatusInterface::PENDING_OPT_IN);

        $this->expectException(PendingOptInException::class);

        $this->contactManager->prepareDotdigitalContact(
            $this->contactModelMock,
            $this->subscriberModelMock,
            $this->getDefaultDataFields()
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

    private function getDummySubscriberDataFields()
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
            ],
            [
                'Key' => 'CONSENTTEXT',
                'Value' => 'You have consented!',
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

    private function getConsentDataFields()
    {
        return [
            [
                'Key' => 'CONSENTTEXT',
                'Value' => 'You have consented!',
            ]
        ];
    }
}
