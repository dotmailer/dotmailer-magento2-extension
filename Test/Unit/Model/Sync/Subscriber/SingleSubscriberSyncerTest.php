<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Subscriber;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Newsletter\OptInTypeFinder;
use Dotdigitalgroup\Email\Model\Sync\Automation\DataField\DataFieldCollector;
use Dotdigitalgroup\Email\Model\Sync\Subscriber\SingleSubscriberSyncer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SingleSubscriberSyncerTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var DataFieldCollector|MockObject
     */
    private $dataFieldCollectorMock;

    /**
     * @var Contact|MockObject
     */
    private $contactModelMock;

    /**
     * @var OptInTypeFinder|MockObject
     */
    private $optInTypeFinderMock;

    /**
     * @var Client|MockObject
     */
    private $clientMock;

    /**
     * @var SingleSubscriberSyncer
     */
    private $singleSubscriberSyncer;

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
        $this->optInTypeFinderMock = $this->createMock(OptInTypeFinder::class);
        $this->clientMock = $this->createMock(Client::class);

        $this->singleSubscriberSyncer = new SingleSubscriberSyncer(
            $this->helperMock,
            $this->dataFieldCollectorMock,
            $this->optInTypeFinderMock
        );
    }

    public function testPushContactToSubscriberAddressBook()
    : void
    {
        $websiteId = 1;
        $subscriberAddressBookId = '123';
        $email = 'chaz@emailsim.io';
        $optInType = 'double';

        $this->contactModelMock->method('getWebsiteId')->willReturn($websiteId);
        $this->contactModelMock->method('getEmail')->willReturn($email);
        $this->helperMock->method('isSubscriberSyncEnabled')->willReturn(true);
        $this->helperMock->method('getSubscriberAddressBook')->willReturn($subscriberAddressBookId);
        $this->helperMock->method('getWebsiteApiClient')->willReturn($this->clientMock);

        $this->dataFieldCollectorMock->method('collectForSubscriber')
            ->willReturn($this->getDummySubscriberDataFields());
        $this->dataFieldCollectorMock->method('mergeFields')
            ->willReturn($this->getDummySubscriberDataFields());

        $this->optInTypeFinderMock->expects($this->once())
            ->method('getOptInType')
            ->willReturn($optInType);

        $this->clientMock->expects($this->once())
            ->method('addContactToAddressBook')
            ->with(
                $this->contactModelMock->getEmail(),
                $subscriberAddressBookId,
                $optInType,
                $this->getDummySubscriberDataFields()
            )
            ->willReturn((object) ['message' => 'success']);

        $result = $this->singleSubscriberSyncer->pushContactToSubscriberAddressBook($this->contactModelMock);

        $this->assertIsObject($result);
        $this->assertEquals('success', $result->message);
    }

    public function testPushContactToSubscriberAddressBookReturnsNullWhenDisabled()
    : void
    {
        $websiteId = 1;

        $this->contactModelMock->method('getWebsiteId')->willReturn($websiteId);
        $this->helperMock->method('isSubscriberSyncEnabled')->willReturn(false);

        $result = $this->singleSubscriberSyncer->pushContactToSubscriberAddressBook($this->contactModelMock);

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
