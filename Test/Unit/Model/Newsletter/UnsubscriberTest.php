<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Newsletter;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Connector\AccountHandler;
use Dotdigitalgroup\Email\Model\Cron\CronFromTimeSetter;
use Dotdigitalgroup\Email\Model\Newsletter\Unsubscriber;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Store\Api\StoreWebsiteRelationInterface;
use PHPUnit\Framework\TestCase;

class UnsubscriberTest extends TestCase
{
    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var CronFromTimeSetter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cronFromTimeSetterMock;

    /**
     * @var Contact|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactResourceMock;

    /**
     * @var ContactCollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var AccountHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $accountHandlerMock;

    /**
     * @var StoreWebsiteRelationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeWebsiteRelationInterfaceMock;

    /**
     * @var Unsubscriber
     */
    private $unsubscriber;

    /**
     * Prepare data
     */
    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->cronFromTimeSetterMock = $this->createMock(CronFromTimeSetter::class);
        $this->contactResourceMock = $this->createMock(Contact::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->accountHandlerMock = $this->createMock(AccountHandler::class);
        $this->storeWebsiteRelationInterfaceMock = $this->createMock(StoreWebsiteRelationInterface::class);

        $this->unsubscriber = new Unsubscriber(
            $this->helperMock,
            $this->cronFromTimeSetterMock,
            $this->contactResourceMock,
            $this->contactCollectionFactoryMock,
            $this->accountHandlerMock,
            $this->storeWebsiteRelationInterfaceMock,
            ['batchSize' => 3]
        );
    }

    public function testSuppressedContactsAreUnsubscribed()
    {
        $this->sharedFlow();

        $contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($contactCollectionMock);

        $contactCollectionMock->expects($this->atLeastOnce())
            ->method('getSubscribersWithScopeAndLastSubscribedAtDate')
            ->willReturnOnConsecutiveCalls(
                $this->getLocalContactsWebsiteOne(),
                $this->getLocalContactsWebsiteOne(),
                $this->getLocalContactsWebsiteTwo()
            );

        $filterMethod = self::getMethod('filterRecentlyResubscribedEmails');
        $filteredA = $filterMethod->invokeArgs(
            $this->unsubscriber,
            [
            $this->getLocalContactsWebsiteOne(),
            $this->getSuppressedEmails()
            ]
        );

        $filteredB = $filterMethod->invokeArgs(
            $this->unsubscriber,
            [
            $this->getLocalContactsWebsiteTwo(),
            $this->getSuppressedEmails()
            ]
        );

        $this->storeWebsiteRelationInterfaceMock->expects($this->atLeastOnce())
            ->method('getStoreByWebsiteId')
            ->willReturn(['1', '2']);

        $this->contactResourceMock->expects($this->exactly(3))
            ->method('unsubscribeByWebsiteAndStore')
            ->willReturnOnConsecutiveCalls(count($filteredA), count($filteredB), 0);

        $unsubscribes = $this->unsubscriber->run();

        $this->assertEquals(5, $unsubscribes);
    }

    public function testRecentlyResubscribedSuppressedContactsAreNotUnsubscribed()
    {
        $this->sharedFlow();

        $contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($contactCollectionMock);

        $contactCollectionMock->expects($this->atLeastOnce())
            ->method('getSubscribersWithScopeAndLastSubscribedAtDate')
            ->willReturn($this->getLocalContactsRecentlyResubscribed());

        $this->contactResourceMock->expects($this->never())
            ->method('unsubscribeByWebsiteAndStore');

        $unsubscribes = $this->unsubscriber->run();

        $this->assertEquals(0, $unsubscribes);
    }

    public function testFilterMethod()
    {
        $filterMethod = self::getMethod('filterRecentlyResubscribedEmails');
        $filteredA = $filterMethod->invokeArgs(
            $this->unsubscriber,
            [
            $this->getLocalContactsWebsiteOne(),
            $this->getSuppressedEmails()
            ]
        );

        $this->assertEquals(2, count($filteredA));

        $filteredB = $filterMethod->invokeArgs(
            $this->unsubscriber,
            [
            $this->getLocalContactsWebsiteTwo(),
            $this->getSuppressedEmails()
            ]
        );

        $this->assertEquals(3, count($filteredB));
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
            ->method('getContactsSuppressedSinceDate')
            ->willReturnOnConsecutiveCalls(
                $this->getECSuppressedContacts(),
                $this->getECSuppressedContacts(),
                new \stdClass(),
                $this->getECSuppressedContacts(),
                new \stdClass()
            );
    }

    /**
     * Not considered great practice, but especially useful in this case.
     * We want to test that the method filterRecentlyResubscribedEmails()
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
        $class = new \ReflectionClass(Unsubscriber::class);
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
     * 3 recent suppressions.
     *
     * @return \stdClass
     */
    private function getECSuppressedContacts()
    {
        $data = [[
             'suppressedContact' => [
                 'email' => 'ben@onboardingforlivechatforengagementcloudformagento.co.uk',
             ],
             'dateRemoved' => '2019-05-01 00:00:00',
             'reason' => 'Suppressed'
        ],
        [
            'suppressedContact' => [
                'email' => 'test-suppress@emailsim.io',
            ],
            'dateRemoved' => '2019-05-01 00:00:00',
            'reason' => 'Suppressed'
        ],
        [
            'suppressedContact' => [
                'email' => 'i@resubscribedrecently.com',
            ],
            'dateRemoved' => '2019-05-01 00:00:00',
            'reason' => 'Suppressed'
        ]];

        return json_decode(json_encode($data));
    }

    /**
     * Passed to the filterRecentlyResubscribedEmails method.
     */
    private function getSuppressedEmails()
    {
        return [[
            'email' => 'ben@onboardingforlivechatforengagementcloudformagento.co.uk',
            'removed_at' => '2019-05-01 00:00:00'
        ],
        [
            'email' => 'test-suppress@emailsim.io',
            'removed_at' => '2019-05-01 00:00:00'
        ],
        [
            'email' => 'i@resubscribedrecently.com',
            'removed_at' => '2019-05-01 00:00:00'
        ]];
    }

    /**
     * 3 local contacts:
     * - one subscribed more recently than she was removed on EC.
     *
     * @return array
     */
    private function getLocalContactsWebsiteOne()
    {
        return [[
            'email' => 'ben@onboardingforlivechatforengagementcloudformagento.co.uk',
            'last_subscribed_at' => '2019-04-20 00:00:00',
            'website_id' => '1',
            'store_id' => '1',
        ], [
            'email' => 'test-suppress@emailsim.io',
            'last_subscribed_at' => '2019-04-20 00:00:00',
            'website_id' => '1',
            'store_id' => '1',
        ], [
            'email' => 'i@resubscribedrecently.com',
            'last_subscribed_at' => '2019-05-01 01:00:00',
            'website_id' => '1',
            'store_id' => '1',
        ]];
    }

    /**
     * 4 local contacts:
     * - one subscribed more recently than she was removed on EC.
     * - two for the same email subscribed on two websites.
     *
     * @return array
     */
    private function getLocalContactsWebsiteTwo()
    {
        return [[
            'email' => 'ben@onboardingforlivechatforengagementcloudformagento.co.uk',
            'last_subscribed_at' => '2019-04-20 00:00:00',
            'website_id' => '2',
            'store_id' => '2',
        ], [
            'email' => 'test-suppress@emailsim.io',
            'last_subscribed_at' => '2019-04-20 00:00:00',
            'website_id' => '2',
            'store_id' => '3',
        ], [
            'email' => 'test-suppress@emailsim.io',
            'last_subscribed_at' => '2019-04-20 00:00:00',
            'website_id' => '2',
            'store_id' => '4',
        ], [
            'email' => 'test-suppress@emailsim.io',
            'last_subscribed_at' => '2019-05-01 01:00:00',
            'website_id' => '3',
            'store_id' => '5',
        ], [
            'email' => 'i@resubscribedrecently.com',
            'last_subscribed_at' => '2019-05-01 02:00:00',
            'website_id' => '2',
            'store_id' => '3',
        ]];
    }

    /**
     * 3 local contacts, all subscribed more recently than they were removed on EC.
     *
     * @return array
     */
    private function getLocalContactsRecentlyResubscribed()
    {
        return [[
            'email' => 'ben@onboardingforlivechatforengagementcloudformagento.co.uk',
            'last_subscribed_at' => '2019-05-01 01:00:00',
            'store_id' => '1',
        ], [
            'email' => 'test-suppress@emailsim.io',
            'last_subscribed_at' => '2019-05-01 01:00:00',
            'store_id' => '1',
        ], [
            'email' => 'i@resubscribedrecently.com',
            'last_subscribed_at' => '2019-05-01 01:00:00',
            'store_id' => '1',
        ]];
    }
}
