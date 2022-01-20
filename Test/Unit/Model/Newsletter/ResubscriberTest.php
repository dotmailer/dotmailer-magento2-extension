<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Newsletter;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Connector\AccountHandler;
use Dotdigitalgroup\Email\Model\Newsletter\Resubscriber;
use Dotdigitalgroup\Email\Model\Newsletter\SubscriberFilterer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterfaceFactory;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use Magento\Store\Api\StoreWebsiteRelationInterface;
use PHPUnit\Framework\TestCase;

class ResubscriberTest extends TestCase
{
    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var Contact|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactResourceMock;

    /**
     * @var DateTimeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeFactoryMock;

    /**
     * @var TimezoneInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $timezoneInterfaceFactoryMock;

    /**
     * @var AccountHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $accountHandlerMock;

    /**
     * @var StoreWebsiteRelationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeWebsiteRelationInterfaceMock;

    /**
     * @var SubscriberFilterer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subscriberFiltererMock;

    /**
     * @var Resubscriber
     */
    private $model;

    /**
     * Prepare data
     */
    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->contactResourceMock = $this->createMock(Contact::class);
        $this->timezoneInterfaceFactoryMock = $this->createMock(TimezoneInterfaceFactory::class);
        $this->dateTimeFactoryMock = $this->createMock(DateTimeFactory::class);
        $this->accountHandlerMock = $this->createMock(AccountHandler::class);
        $this->storeWebsiteRelationInterfaceMock = $this->createMock(StoreWebsiteRelationInterface::class);
        $this->subscriberFiltererMock = $this->createMock(SubscriberFilterer::class);

        $this->model = new Resubscriber(
            $this->helperMock,
            $this->contactResourceMock,
            $this->dateTimeFactoryMock,
            $this->timezoneInterfaceFactoryMock,
            $this->accountHandlerMock,
            $this->storeWebsiteRelationInterfaceMock,
            $this->subscriberFiltererMock
        );
    }

    /**
     * We start with 2 API users, with 2 batches for account 1 and 1 batch for account 2.
     * Each of the 3 batches has 4 contacts. Two of these have subscribed recently (the others are ditched).
     * Finally, of the 2 qualifying contacts in each batch, only 1 has an older change_status_at
     * (the other has unsubscribed more recently).
     */
    public function testRecentlyModifiedSubscribersAreResubscribed()
    {
        $this->generateTimezoneDate();
        $this->sharedFlow();

        $subscriberCollectionMock = $this->createMock(SubscriberCollection::class);
        $subscriberModelMock = $this->getMockBuilder(\Magento\Newsletter\Model\Subscriber::class)
            ->addMethods(['getSubscriberEmail', 'getChangeStatusAt', 'getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriberFiltererMock->expects($this->atLeastOnce())
            ->method('getSubscribersByEmailsStoresAndStatus')
            ->willReturn($subscriberCollectionMock);

        $this->storeWebsiteRelationInterfaceMock->expects($this->atLeastOnce())
            ->method('getStoreByWebsiteId')
            ->willReturn(['1', '2']);

        $subscriberCollectionMock->expects($this->exactly(3))
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$subscriberModelMock, $subscriberModelMock]));

        $subscriberModelMock->expects($this->any())
            ->method('getSubscriberEmail')
            ->willReturnOnConsecutiveCalls(
                'chaz@emailsim.io',
                'chaz2@emailsim.io',
                'chaz@emailsim.io',
                'chaz2@emailsim.io',
                'chaz@emailsim.io',
                'chaz2@emailsim.io'
            );

        $subscriberModelMock->expects($this->any())
            ->method('getChangeStatusAt')
            ->willReturnOnConsecutiveCalls(
                '2021-11-10T11:24:08.94976Z',
                '2021-11-19T11:24:08.94976Z',
                '2021-11-10T11:24:08.94976Z',
                '2021-11-19T11:24:08.94976Z',
                '2021-11-10T11:24:08.94976Z',
                '2021-11-19T12:00:00.94976Z'
            );

        $subscriberModelMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(1);

        $this->contactResourceMock->expects($this->exactly(3))
            ->method('subscribeByEmailAndStore')
            ->willReturn(1);

        $unsubscribes = $this->model->subscribe(4);

        $this->assertEquals(3, $unsubscribes);
    }

    public function testContactsAreNotUpdatedIfChangeStatusAtIsNewer()
    {
        $this->generateTimezoneDate();
        $this->sharedFlow();

        $subscriberCollectionMock = $this->createMock(SubscriberCollection::class);
        $subscriberModelMock = $this->getMockBuilder(\Magento\Newsletter\Model\Subscriber::class)
            ->addMethods(['getSubscriberEmail', 'getChangeStatusAt', 'getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->subscriberFiltererMock->expects($this->atLeastOnce())
            ->method('getSubscribersByEmailsStoresAndStatus')
            ->willReturn($subscriberCollectionMock);

        $this->storeWebsiteRelationInterfaceMock->expects($this->atLeastOnce())
            ->method('getStoreByWebsiteId')
            ->willReturn(['1', '2']);

        $subscriberCollectionMock->expects($this->exactly(3))
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$subscriberModelMock, $subscriberModelMock]));

        $subscriberModelMock->expects($this->any())
            ->method('getSubscriberEmail')
            ->willReturnOnConsecutiveCalls(
                'chaz@emailsim.io',
                'chaz2@emailsim.io',
                'chaz@emailsim.io',
                'chaz2@emailsim.io',
                'chaz@emailsim.io',
                'chaz2@emailsim.io'
            );

        $subscriberModelMock->expects($this->any())
            ->method('getChangeStatusAt')
            ->willReturn('2021-11-19T12:24:08.94976Z');

        $subscriberModelMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(1);

        $this->contactResourceMock->expects($this->exactly(3))
            ->method('subscribeByEmailAndStore')
            ->willReturn(0);

        $unsubscribes = $this->model->subscribe(4);

        $this->assertEquals(0, $unsubscribes);
    }

    public function testFilterModifiedContactsMethod()
    {
        $this->generateTimezoneDate();

        $filterMethod = self::getMethod('filterModifiedContacts');
        $filtered = $filterMethod->invokeArgs($this->model, [
            $this->getDotdigitalModifiedContacts()
        ]);

        $this->assertEquals(2, count($filtered));
    }

    private function sharedFlow()
    {
        $this->accountHandlerMock->expects($this->once())
            ->method('getAPIUsersForECEnabledWebsites')
            ->willReturn($this->getApiUserData());

        $clientMock = $this->createMock(Client::class);
        $this->helperMock->expects($this->atLeastOnce())
            ->method('getWebsiteApiClient')
            ->willReturn($clientMock);

        // Two batches for the first website id, one for the second
        $clientMock->expects($this->atLeastOnce())
            ->method('getContactsModifiedSinceDate')
            ->willReturnOnConsecutiveCalls(
                $this->getDotdigitalModifiedContacts(),
                $this->getDotdigitalModifiedContacts(),
                new \stdClass(),
                $this->getDotdigitalModifiedContacts(),
                new \stdClass()
            );
    }

    private function generateTimezoneDate()
    {
        $fromTime = '2021-11-18T12:17:47+00:00';

        $dateTimeMock = $this->createMock(\DateTime::class);
        $magentoDateTimeMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $timezoneInterfaceMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);
        $this->timezoneInterfaceFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($timezoneInterfaceMock);
        $timezoneInterfaceMock->expects($this->once())
            ->method('date')
            ->willReturn($dateTimeMock);
        $dateTimeMock->expects($this->once())
            ->method('sub');

        $this->dateTimeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($magentoDateTimeMock);
        $magentoDateTimeMock->expects($this->once())
            ->method('date')
            ->willReturn($fromTime);
    }

    /**
     * Not considered great practice, but especially useful in this case.
     * We want to test that the method filterModifiedContacts()
     * returns the expected filtered array, and this approach is more convenient
     * than putting the method either in its own class, or in the Contact
     * Resource Model (where it doesn't really belong).
     *
     * @param $name
     * @return \ReflectionMethod
     * @throws \ReflectionException
     */
    private static function getMethod($name)
    {
        $class = new \ReflectionClass(Resubscriber::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Mocked EC account config.
     */
    private function getApiUserData()
    {
        return [
            'apiuser-12345@apiconnector.com' => [
                'websites' => ['0', '1']
            ],
            'apiuser-6789apiconnector.com' => [
                'websites' => ['2', '3']
            ],
        ];
    }

    /**
     * 4 recent modified contacts.
     *
     * @return \stdClass[]
     */
    private function getDotdigitalModifiedContacts()
    {
        $contact1 = new \StdClass();
        $contact1->id = 258613273;
        $contact1->email = 'chaz@emailsim.io';
        $recentLastSubscribedDataField = new \StdClass();
        $recentLastSubscribedDataField->key = 'LASTSUBSCRIBED';
        $recentLastSubscribedDataField->value = '2021-11-19T11:24:08.94976Z';
        $contact1->dataFields = [$recentLastSubscribedDataField];

        $contact2 = new \StdClass();
        $contact2->id = 258613273;
        $contact2->email = 'chaz2@emailsim.io';
        $recentLastSubscribedDataField2 = new \StdClass();
        $recentLastSubscribedDataField2->key = 'LASTSUBSCRIBED';
        $recentLastSubscribedDataField2->value = '2021-11-19T11:00:00.94976Z';
        $contact2->dataFields = [$recentLastSubscribedDataField2];

        $contact3 = new \StdClass();
        $contact3->id = 258613273;
        $contact3->email = 'chaz3@emailsim.io';
        $olderLastSubscribedDataField = new \StdClass();
        $olderLastSubscribedDataField->key = 'LASTSUBSCRIBED';
        $olderLastSubscribedDataField->value = '2021-11-17T11:24:08.94976Z';
        $contact3->dataFields = [$olderLastSubscribedDataField];

        $contact4 = new \StdClass();
        $contact4->id = 258613273;
        $contact4->email = 'chaz-never-subscribed@emailsim.io';
        $randomDataField = new \StdClass();
        $randomDataField->key = 'RANDOM_DF';
        $randomDataField->value = 'chaz';
        $contact4->dataFields = [$randomDataField];

        return [$contact1, $contact2, $contact3, $contact4];
    }
}
