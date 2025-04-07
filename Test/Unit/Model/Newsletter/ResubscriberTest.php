<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Newsletter;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Cron\CronFromTimeSetter;
use Dotdigitalgroup\Email\Model\Newsletter\Resubscriber;
use Dotdigitalgroup\Email\Model\Newsletter\SubscriberFilterer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Magento\Newsletter\Model\ResourceModel\Subscriber\Collection as SubscriberCollection;
use Magento\Store\Api\StoreWebsiteRelationInterface;
use PHPUnit\Framework\TestCase;

class ResubscriberTest extends TestCase
{
    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var CronFromTimeSetter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cronFromTimeSetterMock;

    /**
     * @var Contact|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactResourceMock;

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
        $this->loggerMock = $this->createMock(Logger::class);
        $this->cronFromTimeSetterMock = $this->createMock(CronFromTimeSetter::class);
        $this->contactResourceMock = $this->createMock(Contact::class);
        $this->storeWebsiteRelationInterfaceMock = $this->createMock(StoreWebsiteRelationInterface::class);
        $this->subscriberFiltererMock = $this->createMock(SubscriberFilterer::class);

        $this->model = new Resubscriber(
            $this->loggerMock,
            $this->cronFromTimeSetterMock,
            $this->contactResourceMock,
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

        $subscriberCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$subscriberModelMock, $subscriberModelMock]));

        $subscriberModelMock->expects($this->any())
            ->method('getSubscriberEmail')
            ->willReturnOnConsecutiveCalls(
                'chaz@emailsim.io',
                'chaz2@emailsim.io'
            );

        $subscriberModelMock->expects($this->any())
            ->method('getChangeStatusAt')
            ->willReturnOnConsecutiveCalls(
                '2021-11-10T11:23:08.94976Z',
                '2021-11-10T11:23:08.94976Z',
                '2021-11-10T11:24:08.94976Z',
                '2021-11-10T11:24:08.94976Z'
            );

        $subscriberModelMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(1);

        $this->contactResourceMock->expects($this->once())
            ->method('subscribeByEmailAndStore')
            ->willReturn(1);

        $this->model->processBatch(
            $this->getDotdigitalModifiedContacts(),
            [1, 2]
        );
    }

    public function testContactsAreNotUpdatedIfChangeStatusAtIsNewer()
    {
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

        $subscriberCollectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$subscriberModelMock, $subscriberModelMock]));

        $subscriberModelMock->expects($this->any())
            ->method('getSubscriberEmail')
            ->willReturnOnConsecutiveCalls(
                'chaz@emailsim.io',
                'chaz2@emailsim.io'
            );

        $subscriberModelMock->expects($this->any())
            ->method('getChangeStatusAt')
            ->willReturn('2021-11-19T12:34:08.94976Z');

        $subscriberModelMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn(1);

        $this->contactResourceMock->expects($this->once())
            ->method('subscribeByEmailAndStore')
            ->willReturn(0);

        $this->loggerMock->expects($this->never())
            ->method('info');

        $this->model->processBatch(
            $this->getDotdigitalModifiedContacts(),
            [1, 2]
        );
    }

    public function testFilterModifiedContactsMethod()
    {
        $this->sharedFlow();

        $filterMethod = self::getMethod('filterModifiedContacts');
        $filtered = $filterMethod->invokeArgs(
            $this->model,
            [
            $this->getDotdigitalModifiedContacts()
            ]
        );

        $this->assertEquals(2, count($filtered));
    }

    /**
     * Not considered great practice, but especially useful in this case.
     * We want to test that the method filterModifiedContacts()
     * returns the expected filtered array, and this approach is more convenient
     * than putting the method either in its own class, or in the Contact
     * Resource Model (where it doesn't really belong).
     *
     * @param  $name
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

    private function sharedFlow()
    {
        $this->cronFromTimeSetterMock->expects($this->any())
            ->method('getFromTime')
            ->willReturn('2021-11-18T12:17:47+00:00');
    }

    /**
     * 4 recent modified contacts.
     *
     * @return SdkContact[]
     * @throws \Exception
     */
    private function getDotdigitalModifiedContacts()
    {
        $contact1 = new SdkContact();
        $contact1->setIdentifiers(['email' => 'chaz@emailsim.io']);
        $contact1->setDataFields(['LASTSUBSCRIBED' => '2021-11-19T11:24:08.94976Z']);

        $contact2 = new SdkContact();
        $contact2->setIdentifiers(['email' => 'chaz2@emailsim.io']);
        $contact2->setDataFields(['LASTSUBSCRIBED' => '2021-11-19T11:00:00.94976Z']);

        $contact3 = new SdkContact();
        $contact3->setIdentifiers(['email' => 'chaz3@emailsim.io']);
        $contact3->setDataFields(['LASTSUBSCRIBED' => '2021-11-17T11:24:08.94976Z']);

        $contact4 = new SdkContact();
        $contact4->setIdentifiers(['email' => 'chaz-never-subscribed@emailsim.io']);
        $contact4->setDataFields(['RANDOM_DF' => 'chaz']);

        $contact5 = new SdkContact();
        $contact5->setIdentifiers(['email' => 'chaz-no-datafields@emailsim.io']);
        $contact5->setDataFields([]);

        return [$contact1, $contact2, $contact3, $contact4, $contact5];
    }
}
