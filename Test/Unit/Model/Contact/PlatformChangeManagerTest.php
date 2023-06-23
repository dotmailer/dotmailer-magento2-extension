<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Contact;

use Dotdigital\V3\Models\Collection;
use Dotdigital\V3\Resources\Contacts;
use Dotdigital\V3\Utility\Pagination\ParameterCollection;
use Dotdigital\V3\Utility\Pagination\ParameterCollectionFactory;
use Dotdigital\V3\Utility\Paginator;
use Dotdigital\V3\Utility\PaginatorFactory;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Connector\AccountHandler;
use Dotdigitalgroup\Email\Model\Contact\ContactUpdaterPool;
use Dotdigitalgroup\Email\Model\Contact\PlatformChangeManager;
use Dotdigitalgroup\Email\Model\Cron\CronFromTimeSetter;
use PHPUnit\Framework\TestCase;

class PlatformChangeManagerTest extends TestCase
{
    /**
     * @var ParameterCollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $parameterCollectionFactoryMock;

    /**
     * @var PaginatorFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paginatorFactoryMock;

    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var ClientFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $clientFactoryMock;

    /**
     * @var AccountHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $accountHandlerMock;

    /**
     * @var CronFromTimeSetter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cronFromTimeSetterMock;

    /**
     * @var ContactUpdaterPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactUpdaterPoolMock;

    /**
     * @var PlatformChangeManager
     */
    private $model;

    /**
     * Set up
     */
    protected function setUp() :void
    {
        $this->parameterCollectionFactoryMock = $this->createMock(ParameterCollectionFactory::class);
        $this->paginatorFactoryMock = $this->createMock(PaginatorFactory::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->accountHandlerMock = $this->createMock(AccountHandler::class);
        $this->cronFromTimeSetterMock = $this->createMock(CronFromTimeSetter::class);
        $this->contactUpdaterPoolMock = $this->createMock(ContactUpdaterPool::class);

        $this->model = new PlatformChangeManager(
            $this->parameterCollectionFactoryMock,
            $this->paginatorFactoryMock,
            $this->loggerMock,
            $this->clientFactoryMock,
            $this->accountHandlerMock,
            $this->cronFromTimeSetterMock,
            $this->contactUpdaterPoolMock
        );
    }

    public function testContactsAreBatchedAndPassedToContactUpdaterPool()
    {
        $this->accountHandlerMock->expects($this->once())
            ->method('getAPIUsersForECEnabledWebsites')
            ->willReturn($this->getApiUserData());

        $clientMock = $this->createMock(Client::class);
        $this->clientFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($clientMock);

        $parameterCollectionMock = $this->createMock(ParameterCollection::class);
        $this->parameterCollectionFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($parameterCollectionMock);

        $parameterCollectionMock->expects($this->exactly(8))
            ->method('setParam')
            ->willReturn($parameterCollectionMock);

        $paginatorMock = $this->createMock(Paginator::class);
        $this->paginatorFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturn($paginatorMock);

        $contactsResourceMock = $this->createMock(Contacts::class);
        $clientMock->expects($this->any())
            ->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);

        $paginatorMock->expects($this->exactly(2))
            ->method('setModel')
            ->willReturn($paginatorMock);

        $paginatorMock->expects($this->exactly(2))
            ->method('setResource')
            ->willReturn($paginatorMock);

        $paginatorMock->expects($this->exactly(2))
            ->method('setParameters')
            ->willReturn($paginatorMock);

        $paginatorMock->expects($this->any())
            ->method('paginate')
            ->willReturn($paginatorMock);

        $collectionMock = $this->createMock(Collection::class);
        $paginatorMock->expects($this->any())
            ->method('getItems')
            ->willReturn($collectionMock);

        $collectionMock->expects($this->any())
            ->method('all')
            ->willReturnOnConsecutiveCalls(
                $this->getDotdigitalModifiedContacts(),
                $this->getDotdigitalModifiedContacts(),
                null,
                $this->getDotdigitalModifiedContacts(),
                null
            );

        $paginatorMock->expects($this->any())
            ->method('next')
            ->willReturn($paginatorMock);

        $paginatorMock->expects($this->exactly(3))
            ->method('hasNext')
            ->willReturnOnConsecutiveCalls(
                true,
                true,
                false
            );

        $this->contactUpdaterPoolMock->expects($this->exactly(3))
            ->method('execute');

        $this->model->run();
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
