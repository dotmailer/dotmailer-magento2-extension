<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Sync\Automation;

use Dotdigitalgroup\Email\Exception\PendingOptInException;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\AutomationFactory;
use Dotdigitalgroup\Email\Model\Queue\Data\AutomationData;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationConsumer;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation as AutomationResource;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessor;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\AutomationTypeHandler;
use Dotdigitalgroup\Email\Model\Sync\Automation\Type\AbandonedCart;
use Dotdigitalgroup\Email\Model\Sync\Automation\Type\AbandonedCartFactory;
use Dotdigitalgroup\Email\Model\Sync\Automation\Sender;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AutomationConsumerTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $logger;

    /**
     * @var AutomationFactory|MockObject
     */
    private $automationFactory;

    /**
     * @var AutomationResource|MockObject
     */
    private $automationResource;

    /**
     * @var AutomationProcessorFactory|MockObject
     */
    private $automationProcessorFactory;

    /**
     * @var AbandonedCartFactory|MockObject
     */
    private $abandonedCartAutomationFactory;

    /**
     * @var Sender|MockObject
     */
    private $sender;

    /**
     * @var AutomationConsumer
     */
    private $automationConsumer;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->automationFactory = $this->createMock(AutomationFactory::class);
        $this->automationResource = $this->createMock(AutomationResource::class);
        $this->automationProcessorFactory = $this->createMock(AutomationProcessorFactory::class);
        $this->abandonedCartAutomationFactory = $this->createMock(AbandonedCartFactory::class);
        $this->sender = $this->createMock(Sender::class);

        $this->automationConsumer = new AutomationConsumer(
            $this->logger,
            $this->automationResource,
            $this->automationFactory,
            $this->automationProcessorFactory,
            $this->abandonedCartAutomationFactory,
            $this->sender
        );
    }

    public function testProcess(): void
    {
        $automationData = new AutomationData();
        $automationData->setId(1);
        $this->setupAutomation();

        $automationProcessorMock = $this->createMock(AutomationProcessor::class);
        $this->automationProcessorFactory->expects($this->once())
            ->method('create')
            ->willReturn($automationProcessorMock);

        $automationProcessorMock->expects($this->once())
            ->method('assembleDataForEnrolment')
            ->willReturn(12345);

        $this->sender->expects($this->once())->method('sendAutomationEnrolments');

        $this->automationConsumer->process($automationData);
    }

    public function testProcessForAbandonedCarts(): void
    {
        $automationData = new AutomationData();
        $automationData->setId(1);
        $this->setupAutomation(AutomationTypeHandler::AUTOMATION_TYPE_ABANDONED_CART_PROGRAM_ENROLMENT);

        $this->automationProcessorFactory->expects($this->never())
            ->method('create');

        $automationProcessorMock = $this->createMock(AbandonedCart::class);
        $this->abandonedCartAutomationFactory->expects($this->once())
            ->method('create')
            ->willReturn($automationProcessorMock);

        $automationProcessorMock->expects($this->once())
            ->method('assembleDataForEnrolment')
            ->willReturn(12345);

        $this->sender->expects($this->once())->method('sendAutomationEnrolments');

        $this->automationConsumer->process($automationData);
    }

    public function testProcessHandlesPendingOptInException(): void
    {
        $automationData = new AutomationData();
        $automationData->setId(1);
        $this->setupAutomation();

        $automationProcessorMock = $this->createMock(AutomationProcessor::class);
        $this->automationProcessorFactory->expects($this->once())
            ->method('create')
            ->willReturn($automationProcessorMock);

        $automationProcessorMock->method('assembleDataForEnrolment')
            ->willThrowException(new PendingOptInException(__('Contact status is PendingOptIn, cannot be enrolled.')));

        $this->sender->expects($this->never())
            ->method('sendAutomationEnrolments');

        $this->automationResource->expects($this->once())
            ->method('setStatusAndSaveAutomation');

        $this->automationConsumer->process($automationData);
    }

    public function testProcessHandlesLocalizedException(): void
    {
        $automationData = new AutomationData();
        $automationData->setId(1);
        $this->setupAutomation();

        $automationProcessorMock = $this->createMock(AutomationProcessor::class);
        $this->automationProcessorFactory->expects($this->once())
            ->method('create')
            ->willReturn($automationProcessorMock);

        $automationProcessorMock->method('assembleDataForEnrolment')
            ->willThrowException(new LocalizedException(__('Unspecified snafu.')));

        $this->sender->expects($this->never())
            ->method('sendAutomationEnrolments');

        $this->automationResource->expects($this->once())
            ->method('setStatusAndSaveAutomation');

        $this->automationConsumer->process($automationData);
    }

    private function setupAutomation($type = AutomationTypeHandler::AUTOMATION_TYPE_NEW_CUSTOMER)
    {
        $model = $this->getMockBuilder(\Dotdigitalgroup\Email\Model\Automation::class)
            ->addMethods([
                'getAutomationType',
                'getWebsiteId',
                'getProgramId'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $model->method('getAutomationType')->willReturn($type);
        $model->method('getWebsiteId')->willReturn(1);
        $model->method('getProgramId')->willReturn(1);

        $this->automationFactory->method('create')->willReturn($model);
        $this->automationResource->method('load')->willReturn($model);
    }
}
