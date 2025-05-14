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
use Dotdigitalgroup\Email\Model\Queue\Newsletter\SubscriptionConsumer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;
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

        $this->helperMock->method('getWebsiteApiClient')->willReturn($this->clientMock);

        $this->subscriptionConsumer = new SubscriptionConsumer(
            $this->helperMock,
            $this->loggerMock,
            $this->automationDataFactoryMock,
            $this->contactDataMock,
            $this->contactFactoryMock,
            $this->contactResourceMock,
            $this->publisherMock,
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

    public function testAutomationPublish(): void
    {
        $this->subscriptionDataMock->method('getType')->willReturn('subscribe');

        $contactModelMock = $this->createMock(Contact::class);
        $this->contactFactoryMock->method('create')->willReturn($contactModelMock);

        $this->contactResourceMock->expects($this->once())
            ->method('load')
            ->with($contactModelMock);

        $this->singleSubscriberSyncerMock->expects($this->once())
            ->method('pushContactToSubscriberAddressBook');

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
