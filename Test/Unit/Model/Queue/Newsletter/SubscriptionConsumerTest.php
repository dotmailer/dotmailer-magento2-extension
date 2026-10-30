<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Newsletter;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Connector\ContactData;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\Queue\Data\AutomationData;
use Dotdigitalgroup\Email\Model\Queue\Data\AutomationDataFactory;
use Dotdigitalgroup\Email\Model\Queue\Data\SubscriptionData;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SingleSubscriberSyncer;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SubscriberListResolver;
use Dotdigitalgroup\Email\Model\Queue\Newsletter\SubscriptionConsumer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;
use Dotdigitalgroup\Email\Test\Unit\Traits\MockBuilderCompatibilityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class SubscriptionConsumerTest extends TestCase
{
    use MockBuilderCompatibilityTrait;

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
     * @var AutomationDataFactory|MockObject
     */
    private $automationDataFactoryMock;

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
     * @var PublisherInterface|MockObject
     */
    private $publisherMock;

    /**
     * @var SubscriberListResolver|MockObject
     */
    private $subscriberListResolverMock;

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
        $this->automationDataFactoryMock = $this->createMock(AutomationDataFactory::class);
        $this->contactResourceMock = $this->createMock(ContactResource::class);
        $this->singleSubscriberSyncerMock = $this->createMock(SingleSubscriberSyncer::class);
        $this->clientMock = $this->createMock(Client::class);
        $this->subscriptionDataMock = $this->createMock(SubscriptionData::class);
        $this->publisherMock = $this->createMock(PublisherInterface::class);

        $this->subscriberListResolverMock = $this->createMock(SubscriberListResolver::class);

        $this->helperMock->method('getWebsiteApiClient')->willReturn($this->clientMock);
        $this->subscriberListResolverMock->method('resolveStoreId')
            ->willReturnCallback(function (int $websiteId, ?int $storeId, ?int $contactStoreId) {
                return $storeId ?: (int) $contactStoreId;
            });

        $this->subscriptionConsumer = new SubscriptionConsumer(
            $this->helperMock,
            $this->loggerMock,
            $this->automationDataFactoryMock,
            $this->contactDataMock,
            $this->contactFactoryMock,
            $this->contactResourceMock,
            $this->publisherMock,
            $this->singleSubscriberSyncerMock,
            $this->subscriberListResolverMock
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
        $this->subscriptionDataMock->method('getStoreId')->willReturn(4);

        $contactModelMock = $this->createMock(Contact::class);
        $this->contactFactoryMock->method('create')->willReturn($contactModelMock);

        $this->contactResourceMock->expects($this->once())
            ->method('load')
            ->with($contactModelMock);

        $this->singleSubscriberSyncerMock->expects($this->once())
            ->method('execute')
            ->with($contactModelMock, 4);

        $this->subscriptionConsumer->process($this->subscriptionDataMock);
    }

    public function testQueuedMessageWithoutStoreFallsBackToContactStore(): void
    {
        $this->subscriptionDataMock->method('getType')->willReturn('unsubscribe');
        $this->subscriptionDataMock->method('getWebsiteId')->willReturn(1);
        $this->subscriptionDataMock->method('getStoreId')->willReturn(null);

        $contactModelMock = $this->getMockBuilder($this->classWithAddedMethods(Contact::class, ['getStoreId']))
            ->onlyMethods(['getStoreId'])
            ->disableOriginalConstructor()
            ->getMock();
        $contactModelMock->method('getStoreId')->willReturn(7);
        $this->contactFactoryMock->method('create')->willReturn($contactModelMock);

        $this->subscriberListResolverMock->expects($this->once())
            ->method('getListId')
            ->with(1, 7)
            ->willReturn(555);

        $this->clientMock->method('updateContactDatafieldsByEmail')->willReturn((object) ['id' => 12]);
        $this->clientMock->expects($this->once())
            ->method('deleteAddressBookContact')
            ->with(555, 12);

        $this->subscriptionConsumer->process($this->subscriptionDataMock);
    }

    public function testUnsubscribeUsesListOfMessageStore(): void
    {
        $this->subscriptionDataMock->method('getType')->willReturn('unsubscribe');
        $this->subscriptionDataMock->method('getWebsiteId')->willReturn(1);
        $this->subscriptionDataMock->method('getStoreId')->willReturn(3);

        $this->subscriberListResolverMock->expects($this->once())
            ->method('getListId')
            ->with(1, 3)
            ->willReturn(333);

        $this->clientMock->method('updateContactDatafieldsByEmail')->willReturn((object) ['id' => 12]);
        $this->clientMock->expects($this->once())
            ->method('deleteAddressBookContact')
            ->with(333, 12);

        $this->subscriptionConsumer->process($this->subscriptionDataMock);
    }

    public function testProcessFailsExplicitlyWhenStoreContextIsUnavailable(): void
    {
        $resolver = $this->createMock(SubscriberListResolver::class);
        $resolver->method('resolveStoreId')
            ->willThrowException(new LocalizedException(__('Subscriber store context is unavailable')));
        $consumer = new SubscriptionConsumer(
            $this->helperMock,
            $this->loggerMock,
            $this->automationDataFactoryMock,
            $this->contactDataMock,
            $this->contactFactoryMock,
            $this->contactResourceMock,
            $this->publisherMock,
            $this->singleSubscriberSyncerMock,
            $resolver
        );
        $this->subscriptionDataMock->method('getType')->willReturn('unsubscribe');
        $this->contactFactoryMock->method('create')->willReturn($this->createMock(Contact::class));

        $this->clientMock->expects($this->never())->method('deleteAddressBookContact');
        $this->expectException(LocalizedException::class);

        $consumer->process($this->subscriptionDataMock);
    }

    public function testProcessUnsubscribe(): void
    {
        $this->subscriptionDataMock->method('getType')->willReturn('unsubscribe');
        $this->subscriptionDataMock->method('getStoreId')->willReturn(3);

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
        $this->subscriptionDataMock->method('getStoreId')->willReturn(3);

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

    public function testAutomationPublish(): void
    {
        $this->subscriptionDataMock->method('getType')->willReturn('subscribe');
        $this->subscriptionDataMock->method('getStoreId')->willReturn(4);

        $contactModelMock = $this->createMock(Contact::class);
        $this->contactFactoryMock->method('create')->willReturn($contactModelMock);

        $this->contactResourceMock->expects($this->once())
            ->method('load')
            ->with($contactModelMock);

        $this->singleSubscriberSyncerMock->expects($this->once())
            ->method('execute');

        $this->subscriptionDataMock->expects($this->exactly(2))
            ->method('getAutomationId')
            ->willReturn(1234567);

        $automationDataMock = $this->createMock(AutomationData::class);
        $this->automationDataFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($automationDataMock);

        $automationDataMock->expects($this->once())
            ->method('setId')
            ->with('1234567');
        $automationDataMock->expects($this->once())
            ->method('setType')
            ->with('subscriber_automation');

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with('ddg.sync.automation', $automationDataMock);

        $this->subscriptionConsumer->process($this->subscriptionDataMock);
    }
}
