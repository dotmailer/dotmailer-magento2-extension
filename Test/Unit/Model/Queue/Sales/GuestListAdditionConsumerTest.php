<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Sales;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigital\V3\Models\ContactFactory as SdkContactFactory;
use Dotdigital\V3\Resources\Contacts;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Newsletter\OptInTypeFinder;
use Dotdigitalgroup\Email\Model\Queue\Data\GuestListAdditionData;
use Dotdigitalgroup\Email\Model\Queue\Sales\GuestListAdditionConsumer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class GuestListAdditionConsumerTest extends TestCase
{
    private const EMAIL = 'guest@example.com';

    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var SdkContactFactory|MockObject
     */
    private $sdkContactFactoryMock;

    /**
     * @var OptInTypeFinder|MockObject
     */
    private $optInTypeFinderMock;

    /**
     * @var ContactCollectionFactory|MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var OrderCollectionFactory|MockObject
     */
    private $orderCollectionFactoryMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var Contacts|MockObject
     */
    private $contactsResourceMock;

    /**
     * @var GuestListAdditionConsumer
     */
    private $consumer;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->sdkContactFactoryMock = $this->createMock(SdkContactFactory::class);
        $this->optInTypeFinderMock = $this->createMock(OptInTypeFinder::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->orderCollectionFactoryMock = $this->createMock(OrderCollectionFactory::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->contactsResourceMock = $this->createMock(Contacts::class);

        $client = $this->createMock(Client::class);
        $client->method('__get')->with('contacts')->willReturn($this->contactsResourceMock);
        $this->clientFactoryMock->method('create')->willReturn($client);

        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->helperMock->method('isGuestSyncEnabled')->willReturn(true);
        $this->helperMock->method('isOnlySubscribersForContactSync')->willReturn(false);

        $this->consumer = new GuestListAdditionConsumer(
            $this->helperMock,
            $this->loggerMock,
            $this->clientFactoryMock,
            $this->sdkContactFactoryMock,
            $this->optInTypeFinderMock,
            $this->contactCollectionFactoryMock,
            $this->orderCollectionFactoryMock,
            $this->storeManagerMock
        );
    }

    public function testContactIsAddedToTheListOfTheOrderStoreWithoutDataFields()
    {
        $this->givenStoreAndList(2, 1, 222);
        $this->givenOrder(2);
        $this->givenContact();

        $sdkContact = $this->createMock(SdkContact::class);
        $this->sdkContactFactoryMock->method('create')->willReturn($sdkContact);
        $sdkContact->expects($this->once())->method('setLists')->with([222]);
        $sdkContact->expects($this->never())->method('setDataFields');
        $this->contactsResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->with(self::EMAIL, $sdkContact);

        $this->consumer->process($this->message(1, 2));
    }

    public function testOrdersInDifferentStoresAreRoutedToTheirOwnLists()
    {
        $this->helperMock->method('getGuestListByStoreId')
            ->willReturnMap([[2, '222'], [3, '333']]);
        $this->storeManagerMock->method('getStore')->willReturnCallback(
            function ($id) {
                $store = $this->createMock(StoreInterface::class);
                $store->method('getWebsiteId')->willReturn(1);
                return $store;
            }
        );
        $this->givenContact();

        $lists = [];
        $this->sdkContactFactoryMock->method('create')->willReturnCallback(
            function () use (&$lists) {
                $sdkContact = $this->createMock(SdkContact::class);
                $sdkContact->method('setLists')->willReturnCallback(
                    function ($value) use (&$lists, $sdkContact) {
                        $lists[] = $value;
                        return $sdkContact;
                    }
                );
                return $sdkContact;
            }
        );

        $this->orderCollectionFactoryMock->method('create')->willReturnOnConsecutiveCalls(
            $this->orderCollection(2, 'A'),
            $this->orderCollection(3, 'B')
        );

        $this->consumer->process($this->message(1, 2, 'A'));
        $this->consumer->process($this->message(1, 3, 'B'));

        $this->assertSame([[222], [333]], $lists);
    }

    public function testPreviouslyImportedContactIsStillAdded()
    {
        $this->givenStoreAndList(3, 1, 333);
        $this->givenOrder(3);
        $this->givenContact(['email_imported' => 1]);
        $this->sdkContactFactoryMock->method('create')->willReturn($this->createMock(SdkContact::class));

        $this->contactsResourceMock->expects($this->once())->method('patchByIdentifier');

        $this->consumer->process($this->message(1, 3));
    }

    public function testDuplicateDeliveryIsAnIdempotentUpsert()
    {
        $this->givenStoreAndList(2, 1, 222);
        $this->orderCollectionFactoryMock->method('create')->willReturnCallback(
            fn () => $this->orderCollection(2)
        );
        $this->givenContact();
        $this->sdkContactFactoryMock->method('create')->willReturnCallback(
            fn () => $this->createMock(SdkContact::class)
        );

        $this->contactsResourceMock->expects($this->exactly(2))->method('patchByIdentifier');

        $this->consumer->process($this->message(1, 2));
        $this->consumer->process($this->message(1, 2));
    }

    public function testNothingIsSentWhenNoListIsMappedForTheStore()
    {
        $this->givenStoreAndList(2, 1, 0);
        $this->contactsResourceMock->expects($this->never())->method('patchByIdentifier');

        $this->consumer->process($this->message(1, 2));
    }

    public function testMissingOrderThrows()
    {
        $this->givenStoreAndList(2, 1, 222);
        $this->orderCollectionFactoryMock->method('create')->willReturn(
            $this->orderCollection(2, 'GUEST-1', false)
        );
        $this->contactsResourceMock->expects($this->never())->method('patchByIdentifier');

        $this->expectException(LocalizedException::class);

        $this->consumer->process($this->message(1, 2));
    }

    public function testRegisteredCustomerOrderIsNotProcessed()
    {
        $this->givenStoreAndList(2, 1, 222);
        $this->givenOrder(2, ['customer_is_guest' => 0]);
        $this->contactsResourceMock->expects($this->never())->method('patchByIdentifier');

        $this->consumer->process($this->message(1, 2));
    }

    public function testCanceledOrderIsNotProcessed()
    {
        $this->givenStoreAndList(2, 1, 222);
        $this->givenOrder(2, ['state' => Order::STATE_CANCELED]);
        $this->contactsResourceMock->expects($this->never())->method('patchByIdentifier');

        $this->consumer->process($this->message(1, 2));
    }

    public function testSuppressedContactIsNotAdded()
    {
        $this->givenStoreAndList(2, 1, 222);
        $this->givenOrder(2);
        $this->givenContact(['suppressed' => 1]);
        $this->contactsResourceMock->expects($this->never())->method('patchByIdentifier');

        $this->consumer->process($this->message(1, 2));
    }

    public function testNonSubscriberIsNotAddedWhenOnlySubscribersAreAllowed()
    {
        $helper = $this->createMock(Data::class);
        $helper->method('isEnabled')->willReturn(true);
        $helper->method('isGuestSyncEnabled')->willReturn(true);
        $helper->method('isOnlySubscribersForContactSync')->willReturn(true);
        $helper->method('getGuestListByStoreId')->willReturn('222');
        $consumer = new GuestListAdditionConsumer(
            $helper,
            $this->loggerMock,
            $this->clientFactoryMock,
            $this->sdkContactFactoryMock,
            $this->optInTypeFinderMock,
            $this->contactCollectionFactoryMock,
            $this->orderCollectionFactoryMock,
            $this->storeManagerMock
        );
        $this->givenStoreAndList(2, 1, 222);
        $this->givenOrder(2);
        $this->givenContact(['is_subscriber' => 0, 'subscriber_status' => Subscriber::STATUS_NOT_ACTIVE]);
        $this->contactsResourceMock->expects($this->never())->method('patchByIdentifier');

        $consumer->process($this->message(1, 2));
    }

    public function testApiFailureIsLoggedAndRethrown()
    {
        $this->givenStoreAndList(2, 1, 222);
        $this->givenOrder(2);
        $this->givenContact();
        $this->sdkContactFactoryMock->method('create')->willReturn($this->createMock(SdkContact::class));
        $this->contactsResourceMock->method('patchByIdentifier')
            ->willThrowException(new \RuntimeException('API down'));
        $this->loggerMock->expects($this->once())->method('error');

        $this->expectException(\RuntimeException::class);

        $this->consumer->process($this->message(1, 2));
    }

    private function message(int $websiteId, int $storeId, string $incrementId = 'GUEST-1'): GuestListAdditionData
    {
        $data = new GuestListAdditionData();
        $data->setWebsiteId($websiteId);
        $data->setStoreId($storeId);
        $data->setContactId(10);
        $data->setEmail(self::EMAIL);
        $data->setOrderIncrementId($incrementId);

        return $data;
    }

    private function givenStoreAndList(int $storeId, int $websiteId, int $listId): void
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getWebsiteId')->willReturn($websiteId);
        $this->storeManagerMock->method('getStore')->with($storeId)->willReturn($store);
        $this->helperMock->method('getGuestListByStoreId')
            ->with($storeId)
            ->willReturn((string) $listId);
    }

    private function givenOrder(int $storeId, array $data = []): void
    {
        $this->orderCollectionFactoryMock->method('create')
            ->willReturn($this->orderCollection($storeId, 'GUEST-1', true, $data));
    }

    private function orderCollection(
        int $storeId,
        string $incrementId = 'GUEST-1',
        bool $exists = true,
        array $data = []
    ): OrderCollection {
        $order = new DataObject(array_merge(
            [
                'id' => $exists ? 99 : null,
                'increment_id' => $incrementId,
                'store_id' => $storeId,
                'customer_is_guest' => 1,
                'customer_email' => self::EMAIL,
                'state' => Order::STATE_NEW,
            ],
            $data
        ));
        $collection = $this->createMock(OrderCollection::class);
        $collection->method('addFieldToFilter')->willReturnSelf();
        $collection->method('setPageSize')->willReturnSelf();
        $collection->method('getFirstItem')->willReturn($order);

        return $collection;
    }

    private function givenContact(array $data = []): void
    {
        $contact = new DataObject(array_merge(
            ['id' => 10, 'customer_id' => 0, 'suppressed' => null, 'is_subscriber' => 1,
                'subscriber_status' => Subscriber::STATUS_SUBSCRIBED],
            $data
        ));
        $collection = $this->createMock(ContactCollection::class);
        $collection->method('loadByCustomerEmail')->willReturn($contact);
        $this->contactCollectionFactoryMock->method('create')->willReturn($collection);
    }
}
