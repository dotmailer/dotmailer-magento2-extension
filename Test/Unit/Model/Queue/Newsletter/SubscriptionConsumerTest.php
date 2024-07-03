<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Newsletter;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\Queue\Data\SubscriptionData;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SingleSubscriberSyncer;
use Dotdigitalgroup\Email\Model\Queue\Newsletter\SubscriptionConsumer;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SubscriptionConsumerTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ContactData|MockObject
     */
    private $contactDataMock;

    /**
     * @var ContactFactory|MockObject
     */
    private $contactFactoryMock;

    /**
     * @var ContactResource|MockObject
     */
    private $contactResourceMock;

    /**
     * @var SingleSubscriberSyncer|MockObject
     */
    private $singleSubscriberSyncerMock;

    /**
     * @var Client|MockObject
     */
    private $clientMock;

    /**
     * @var SubscriptionData|MockObject
     */
    private $subscriptionDataMock;

    /**
     * @var SubscriptionConsumer
     */
    private $subscriptionConsumer;

    protected function setUp(): void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->contactDataMock = $this->createMock(ContactData::class);
        $this->contactFactoryMock = $this->createMock(ContactFactory::class);
        $this->contactResourceMock = $this->createMock(ContactResource::class);
        $this->singleSubscriberSyncerMock = $this->createMock(SingleSubscriberSyncer::class);
        $this->clientMock = $this->createMock(Client::class);
        $this->subscriptionDataMock = $this->createMock(SubscriptionData::class);

        $this->helperMock->method('getWebsiteApiClient')->willReturn($this->clientMock);

        $this->subscriptionConsumer = new SubscriptionConsumer(
            $this->helperMock,
            $this->loggerMock,
            $this->contactDataMock,
            $this->contactFactoryMock,
            $this->contactResourceMock,
            $this->singleSubscriberSyncerMock
        );
    }

    public function testProcessThrowsExceptionOnUnknownType(): void
    {
        $this->subscriptionDataMock->method('getType')->willReturn('');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Unknown subscription type');

        $this->subscriptionConsumer->process($this->subscriptionDataMock);
    }

    public function testProcessSubscribe(): void
    {
        $this->subscriptionDataMock->method('getType')->willReturn('subscribe');

        $contactModelMock = $this->createMock(Contact::class);
        $this->contactFactoryMock->method('create')->willReturn($contactModelMock);

        $this->contactResourceMock->expects($this->once())
            ->method('load')
            ->with($contactModelMock);

        $this->singleSubscriberSyncerMock->expects($this->once())
            ->method('pushContactToSubscriberAddressBook');

        $this->subscriptionConsumer->process($this->subscriptionDataMock);
    }

    public function testProcessUnsubscribe(): void
    {
        $this->subscriptionDataMock->method('getType')->willReturn('unsubscribe');

        $this->contactDataMock->expects($this->once())
            ->method('getSubscriberStatusString')
            ->willReturn('Unsubscribed');

        $this->clientMock->expects($this->once())
            ->method('updateContactDatafieldsByEmail')
            ->willReturn((object) [
                'id' => 1234567
            ]);

        $this->clientMock->expects($this->once())
            ->method('deleteAddressBookContact');

        $this->subscriptionConsumer->process($this->subscriptionDataMock);
    }

    public function testProcessUnsubscribeResultingInLocalSuppression(): void
    {
        $this->subscriptionDataMock->method('getType')->willReturn('unsubscribe');

        $this->contactDataMock->expects($this->once())
            ->method('getSubscriberStatusString')
            ->willReturn('Unsubscribed');

        $this->clientMock->expects($this->once())
            ->method('updateContactDatafieldsByEmail')
            ->willReturn((object) [
                'message' => 'This contact is suppressed!'
            ]);

        $this->contactResourceMock->expects($this->once())
            ->method('setContactSuppressedForContactIds');

        $this->subscriptionConsumer->process($this->subscriptionDataMock);
    }

    public function testProcessResubscribeToList(): void
    {
        $this->subscriptionDataMock->method('getType')->willReturn('resubscribe');

        $this->helperMock->method('getSubscriberAddressBook')->willReturn(123456);

        $this->clientMock->expects($this->once())
            ->method('postAddressBookContactResubscribe');

        $this->subscriptionConsumer->process($this->subscriptionDataMock);
    }

    public function testProcessResubscribeNotToList(): void
    {
        $this->subscriptionDataMock->method('getType')->willReturn('resubscribe');

        $this->helperMock->method('getSubscriberAddressBook')->willReturn(0);

        $this->clientMock->expects($this->once())
            ->method('resubscribeContactByEmail');

        $this->subscriptionConsumer->process($this->subscriptionDataMock);
    }
}
