<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Apiconnector\V3\Contact;

use Dotdigital\V3\Client;
use Dotdigital\V3\Models\Contact as ContactModel;
use Dotdigital\V3\Models\ContactFactory as DotdigitalContactFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Contact\Patcher;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PatcherTest extends TestCase
{
    /**
     * @var DotdigitalContactFactory|MockObject
     */
    private $sdkContactFactory;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactory;

    /**
     * @var ContactResponseHandler|MockObject
     */
    private $contactResponseHandler;

    /**
     * @var Patcher
     */
    private $patcher;

    protected function setUp(): void
    {
        $this->sdkContactFactory = $this->createMock(DotdigitalContactFactory::class);
        $this->clientFactory = $this->createMock(ClientFactory::class);
        $this->contactResponseHandler = $this->createMock(ContactResponseHandler::class);

        $this->patcher = new Patcher(
            $this->sdkContactFactory,
            $this->clientFactory,
            $this->contactResponseHandler
        );
    }

    public function testGetOrCreateContactByEmail(): void
    {
        $email = 'test@example.com';
        $websiteId = 1;
        $storeId = 1;

        $contact = $this->createMock(ContactModel::class);
        $this->sdkContactFactory->expects($this->once())
            ->method('create')
            ->willReturn($contact);

        $client = $this->createMock(Client::class);
        $this->clientFactory->expects($this->once())
            ->method('create')
            ->with(['data' => ['websiteId' => $websiteId]])
            ->willReturn($client);

        $contactsResourceMock = $this->createMock(\Dotdigital\V3\Resources\Contacts::class);
        $client->method('__get')
            ->with('contacts')
            ->willReturn($contactsResourceMock);
        $contactsResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->with($email, $contact)
            ->willReturn($contact);

        $this->contactResponseHandler->expects($this->once())
            ->method('processV3ContactResponse')
            ->with($contact, $websiteId, $storeId)
            ->willReturn($contact);

        $result = $this->patcher->getOrCreateContactByEmail($email, $websiteId, $storeId);

        $this->assertInstanceOf(ContactModel::class, $result);
    }
}
