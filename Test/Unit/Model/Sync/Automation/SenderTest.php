<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Automation;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\Sync\Automation\Sender;
use Dotdigitalgroup\Email\Model\StatusInterface;
use Magento\Framework\Stdlib\DateTime;
use PHPUnit\Framework\TestCase;

class SenderTest extends TestCase
{
    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var AutomationResource|\PHPUnit_Framework_MockObject_MockObject
     */
    private $automationResourceMock;

    /**
     * @var DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeMock;

    /**
     * @var Sender
     */
    private $sender;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->automationResourceMock = $this->createMock(AutomationResource::class);
        $this->dateTimeMock = $this->createMock(DateTime::class);

        $this->sender = new Sender(
            $this->helperMock,
            $this->automationResourceMock,
            $this->dateTimeMock
        );
    }

    public function testContactsAreEnrolled()
    {
        $clientMock = $this->createMock(Client::class);

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->willReturn($clientMock);

        $clientMock->expects($this->once())
            ->method('postProgramsEnrolments');

        $this->automationResourceMock->expects($this->once())
            ->method('updateStatus');

        $this->sender->sendAutomationEnrolments(
            'subscriber_automation',
            [
                'chaz1@emailsim.io',
                'chaz2@emailsim.io',
                'chaz3@emailsim.io'
            ],
            1,
            '12345'
        );
    }

    public function testContactsAreNotEnrolledIfProgramIsNotActive()
    {
        $clientMock = $this->createMock(Client::class);

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->willReturn($clientMock);

        $clientMock->expects($this->once())
            ->method('postProgramsEnrolments')
            ->willReturn($this->getProgramDeactivatedResponse());

        $this->automationResourceMock->expects($this->once())
            ->method('updateStatus')
            ->with(
                [
                    0 => 0,
                    1 => 1,
                    2 => 2,
                ],
                StatusInterface::DEACTIVATED,
                'Error: ERROR_PROGRAM_NOT_ACTIVE',
                null,
                'subscriber_automation'
            );

        $this->sender->sendAutomationEnrolments(
            'subscriber_automation',
            [
                'chaz1@emailsim.io',
                'chaz2@emailsim.io',
                'chaz3@emailsim.io'
            ],
            1,
            '12345'
        );
    }

    public function testContactsAreNotEnrolledIfClientThrowsError()
    {
        $clientMock = $this->createMock(Client::class);

        $this->helperMock->expects($this->exactly(1))
            ->method('getWebsiteApiClient')
            ->willReturn($clientMock);

        $clientMock->expects($this->once())
            ->method('postProgramsEnrolments')
            ->willReturn($this->getFailedResponse());

        $this->automationResourceMock->expects($this->once())
            ->method('updateStatus')
            ->with(
                [0, 1, 2],
                StatusInterface::FAILED,
                'You lose!',
                null,
                'subscriber_automation'
            );

        $this->sender->sendAutomationEnrolments(
            'subscriber_automation',
            [
                'chaz1@emailsim.io',
                'chaz2@emailsim.io',
                'chaz3@emailsim.io'
            ],
            1,
            '12345'
        );
    }

    private function getProgramDeactivatedResponse()
    {
        $result = [
            'message' => 'Error: ERROR_PROGRAM_NOT_ACTIVE'
        ];

        return (object) $result;
    }

    private function getFailedResponse()
    {
        $result = [
            'message' => 'You lose!'
        ];

        return (object) $result;
    }
}
