<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Subscriber;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigital\V3\Resources\Contacts;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Contact;
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
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var SingleSubscriberSyncer
     */
    private $singleSubscriberSyncer;

    /**
     * @var Client|MockObject
     */
    private $v3ClientMock;

    /**
     * @var Contacts|MockObject
     */
    private $contactsResourceMock;

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
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);

        $this->v3ClientMock = $this->createMock(Client::class);
        $this->clientFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->v3ClientMock);
        $this->contactsResourceMock = $this->createMock(Contacts::class);
        $this->v3ClientMock->contacts = $this->contactsResourceMock;

        $this->singleSubscriberSyncer = new SingleSubscriberSyncer(
            $this->helperMock,
            $this->clientFactoryMock,
            $this->dataFieldCollectorMock
        );
    }

    public function testPushContactToSubscriberAddressBook()
    : void
    {
        $websiteId = 1;
        $subscriberAddressBookId = '123';
        $email = 'chaz@emailsim.io';
        $sdkSubscriber = $this->getDummySdkSubscriber();

        $this->contactModelMock->method('getWebsiteId')->willReturn($websiteId);
        $this->contactModelMock->method('getEmail')->willReturn($email);
        $this->helperMock->method('isSubscriberSyncEnabled')->willReturn(true);
        $this->helperMock->method('getSubscriberAddressBook')->willReturn($subscriberAddressBookId);

        $this->dataFieldCollectorMock->method('collectForSubscriber')
            ->willReturn($sdkSubscriber);

        $this->contactsResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->with(
                $this->contactModelMock->getEmail(),
                $sdkSubscriber
            )
            ->willReturn($sdkSubscriber);

        $this->singleSubscriberSyncer->pushContactToSubscriberAddressBook($this->contactModelMock);
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

    private function getDummySdkSubscriber()
    {
        return new SdkContact([
            'identifiers' => [
                'email' => 'chaz@emailsim.io',
            ],
            'channelProperties' => [
                'email' => [
                    'optInType' => 'double',
                    'emailType' => 'html',
                ]
            ],
            'lists' => [123],
            'datafields' => [
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
            ]
        ]);
    }
}
