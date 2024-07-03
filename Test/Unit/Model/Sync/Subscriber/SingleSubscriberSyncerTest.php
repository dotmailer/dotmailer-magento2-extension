<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Subscriber;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SingleSubscriberSyncer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SingleSubscriberSyncerTest extends TestCase
{
    /**
     * @var Data|MockObject $helperMock
     */
    private $helperMock;

    /**
     * @var DataFieldCollector|MockObject
     */
    private $dataFieldCollectorMock;

    /**
     * @var Contact|MockObject $contactModelMock
     */
    private $contactModelMock;

    /**
     * @var Client|MockObject $clientMock
     */
    private $clientMock;

    protected function setUp()
    : void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->dataFieldCollectorMock = $this->createMock(DataFieldCollector::class);
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
        $this->clientMock = $this->createMock(Client::class);
    }

    public function testPushContactToSubscriberAddressBook()
    : void
    {
        $websiteId = 1;
        $subscriberAddressBookId = '123';
        $email = 'chaz@emailsim.io';

        $this->contactModelMock->method('getWebsiteId')->willReturn($websiteId);
        $this->contactModelMock->method('getEmail')->willReturn($email);
        $this->helperMock->method('isSubscriberSyncEnabled')->willReturn(true);
        $this->helperMock->method('getSubscriberAddressBook')->willReturn($subscriberAddressBookId);
        $this->helperMock->method('getWebsiteApiClient')->willReturn($this->clientMock);

        $this->dataFieldCollectorMock->method('collectForSubscriber')
            ->willReturn($this->getDummySubscriberDataFields());
        $this->dataFieldCollectorMock->method('mergeFields')
            ->willReturn($this->getDummySubscriberDataFields());

        $this->clientMock->expects($this->once())
            ->method('addContactToAddressBook')
            ->with(
                $this->contactModelMock->getEmail(),
                $subscriberAddressBookId,
                null,
                $this->getDummySubscriberDataFields()
            )
            ->willReturn((object) ['message' => 'success']);

        $singleSubscriberSyncer = new SingleSubscriberSyncer(
            $this->helperMock,
            $this->dataFieldCollectorMock
        );

        $result = $singleSubscriberSyncer->pushContactToSubscriberAddressBook($this->contactModelMock);

        $this->assertIsObject($result);
        $this->assertEquals('success', $result->message);
    }

    public function testPushContactToSubscriberAddressBookReturnsNullWhenDisabled()
    : void
    {
        $websiteId = 1;

        $this->contactModelMock->method('getWebsiteId')->willReturn($websiteId);
        $this->helperMock->method('isSubscriberSyncEnabled')->willReturn(false);

        $singleSubscriberSyncer = new SingleSubscriberSyncer(
            $this->helperMock,
            $this->dataFieldCollectorMock
        );

        $result = $singleSubscriberSyncer->pushContactToSubscriberAddressBook($this->contactModelMock);

        $this->assertNull($result);
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
}
