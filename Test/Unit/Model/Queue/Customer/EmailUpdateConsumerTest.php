<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Customer;

use Dotdigitalgroup\Email\Model\Queue\Customer\EmailUpdateConsumer;
use Dotdigitalgroup\Email\Model\Queue\Data\EmailUpdateData;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\AutomationFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\CampaignFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\AbandonedFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation;
use Dotdigitalgroup\Email\Model\ResourceModel\Campaign;
use Dotdigitalgroup\Email\Model\ResourceModel\Abandoned;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigital\V3\Models\ContactFactory as DotdigitalContactFactory;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigital\V3\Models\Contact as DotdigitalContact;
use Dotdigital\Resources\AbstractResource;
use Dotdigital\Exception\ResponseValidationException;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact as ContactResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\Contact\ContactResponseHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailUpdateConsumerTest extends TestCase
{
    /**
     * @var EmailUpdateData|EmailUpdateData&MockObject|MockObject
     */
    private $emailUpdateDataMock;

    /**
     * @var Logger|Logger&MockObject|MockObject
     */
    private $loggerMock;

    /**
     * @var AutomationFactory|AutomationFactory&MockObject|MockObject
     */
    private $automationFactoryMock;

    /**
     * @var CampaignFactory|CampaignFactory&MockObject|MockObject
     */
    private $campaignFactoryMock;

    /**
     * @var AbandonedFactory|AbandonedFactory&MockObject|MockObject
     */
    private $abandonedFactoryMock;

    /**
     * @var EmailUpdateConsumer
     */
    private $emailUpdaterConsumer;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var DotdigitalContactFactory|MockObject
     */
    private $sdkContactFactoryMock;

    /**
     * @var Client|Client&MockObject|MockObject
     */
    private $clientMock;

    /**
     * @var Automation|Automation&MockObject|MockObject
     */
    private $automationMock;

    /**
     * @var Campaign|Campaign&MockObject|MockObject
     */
    private $campaignMock;

    /**
     * @var Abandoned|Abandoned&MockObject|MockObject
     */
    private $abandonedMock;

    /**
     * @var AbstractResource&MockObject|MockObject
     */
    private $abstractResourceMock;

    /**
     * @var \Dotdigital\V3\Resources\Contacts|MockObject
     */
    private $sdkContactResourceMock;

    /**
     * @var DotdigitalContact&MockObject|MockObject
     */
    private $responseMock;

    /**
     * @var ContactCollectionFactory|ContactCollectionFactory&MockObject|MockObject
     */
    private $contactCollectionFactoryMock;

    /**
     * @var ContactResource|ContactResource&MockObject|MockObject
     */
    private $contactResourceMock;

    /**
     * @var ContactCollection|ContactCollection&MockObject|MockObject
     */
    private $contactCollectionMock;

    /**
     * @var ContactResponseHandler|MockObject
     */
    private $contactResponseHandlerMock;

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $this->contactCollectionMock = $this->createMock(ContactCollection::class);
        $this->contactCollectionFactoryMock = $this->createMock(ContactCollectionFactory::class);
        $this->contactResourceMock = $this->createMock(ContactResource::class);
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->emailUpdateDataMock = $this->createMock(EmailUpdateData::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->automationFactoryMock = $this->createMock(AutomationFactory::class);
        $this->campaignFactoryMock = $this->createMock(CampaignFactory::class);
        $this->abandonedFactoryMock = $this->createMock(AbandonedFactory::class);
        $this->sdkContactFactoryMock = $this->createMock(DotdigitalContactFactory::class);

        $this->automationMock = $this->createMock(Automation::class);
        $this->campaignMock = $this->createMock(Campaign::class);
        $this->abandonedMock = $this->createMock(Abandoned::class);
        $sdkContactMock = $this->createMock(DotdigitalContact::class);
        $this->clientMock = $this->createMock(Client::class);

        $this->abstractResourceMock = $this->createMock(AbstractResource::class);
        $this->sdkContactResourceMock = $this->createMock(\Dotdigital\V3\Resources\Contacts::class);
        $this->clientMock->contacts = $this->sdkContactResourceMock;

        $this->sdkContactFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($sdkContactMock);

        $sdkContactMock->expects($this->once())
            ->method('setMatchIdentifier');

        $sdkContactMock->expects($this->once())
            ->method('setIdentifiers');

        $this->responseMock = $this->getMockBuilder(DotdigitalContact::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactResponseHandlerMock = $this->createMock(ContactResponseHandler::class);

        $this->emailUpdaterConsumer = new EmailUpdateConsumer(
            $this->clientFactoryMock,
            $this->loggerMock,
            $this->automationFactoryMock,
            $this->campaignFactoryMock,
            $this->abandonedFactoryMock,
            $this->sdkContactFactoryMock,
            $this->contactCollectionFactoryMock,
            $this->contactResourceMock,
            $this->contactResponseHandlerMock
        );
    }

    public function testThatEmailUpdated()
    {
        $emailBefore = 'chaz-email@emailsim.io';
        $emailAfter = 'chaz-email-updated@emailsim.io';
        $websiteId = 1;

        $this->emailUpdateDataMock->expects($this->atLeastOnce())
            ->method('getEmailBefore')
            ->willReturn($emailBefore);

        $this->emailUpdateDataMock->expects($this->atLeastOnce())
            ->method('getEmail')
            ->willReturn($emailAfter);

        $this->emailUpdateDataMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->clientMock);

        $this->sdkContactResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->willReturn($this->responseMock);

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with("Contact email update success", [
                'emailBefore' => $emailBefore,
                'emailAfter' => $emailAfter
            ]);

        $this->contactResponseHandlerMock->expects($this->once())
            ->method('processV3ContactResponse');

        //Confirm that pending rows getting updated.
        $this->automationFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->automationMock);

        $this->abandonedFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->abandonedMock);

        $this->campaignFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->campaignMock);

        //Confirm that we check for orphaned rows
        $this->contactCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('loadNonCustomerByEmailAndWebsiteId')
            ->with($emailAfter, $websiteId)
            ->willReturn($this->contactCollectionMock);

        $this->contactCollectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(0);

        $this->emailUpdaterConsumer->process($this->emailUpdateDataMock);
    }

    public function testThatEmailDidNotUpdate()
    {
        $emailBefore = 'chaz-email@emailsim.io';
        $emailAfter = 'chaz-email-updated@emailsim.io';

        $this->emailUpdateDataMock->expects($this->atLeastOnce())
            ->method('getEmailBefore')
            ->willReturn($emailBefore);

        $this->emailUpdateDataMock->expects($this->atLeastOnce())
            ->method('getEmail')
            ->willReturn($emailAfter);

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->clientMock);

        $this->sdkContactResourceMock->expects($this->once())
            ->method('patchByIdentifier')
            ->willThrowException($e = new ResponseValidationException('Error'));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with("Contact email update error:", [
                'emailBefore' => $emailBefore,
                'emailAfter' => $emailAfter,
                'exception' => $e
            ]);

        $this->contactResponseHandlerMock->expects($this->never())
            ->method('processV3ContactResponse');

        //Confirm that pending rows doesn't get updated.
        $this->automationFactoryMock->expects($this->never())
            ->method('create');

        $this->abandonedFactoryMock->expects($this->never())
            ->method('create');

        $this->campaignFactoryMock->expects($this->never())
            ->method('create');

        //Confirm that we will not check for orphaned rows
        $this->contactCollectionFactoryMock->expects($this->never())
            ->method('create');

        $this->emailUpdaterConsumer->process($this->emailUpdateDataMock);
    }
}
