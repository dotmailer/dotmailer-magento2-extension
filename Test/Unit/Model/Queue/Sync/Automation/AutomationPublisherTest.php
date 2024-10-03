<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Sync\Automation;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Queue\Data\AutomationDataFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AutomationPublisherTest extends TestCase
{
    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var AutomationDataFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $automationDataFactoryMock;

    /**
     * @var PublisherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $publisherMock;

    /**
     * @var AutomationPublisher
     */
    private $automationPublisher;

    protected function setUp(): void
    {

        $this->loggerMock = $this->createMock(Logger::class);
        $this->automationDataFactoryMock = $this->createMock(AutomationDataFactory::class);
        $this->publisherMock = $this->createMock(PublisherInterface::class);

        $this->automationPublisher = new AutomationPublisher(
            $this->loggerMock,
            $this->automationDataFactoryMock,
            $this->publisherMock
        );
    }

    public function testPublish(): void
    {
        $automationMock = $this->getMockBuilder(Automation::class)
            ->onlyMethods(['getId'])
            ->addMethods(['getAutomationType'])
            ->disableOriginalConstructor()
            ->getMock();

        $automationMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $automationMock->expects($this->once())
            ->method('getAutomationType')
            ->willReturn('type');

        $messageMock = $this->createMock(\Dotdigitalgroup\Email\Model\Queue\Data\AutomationData::class);

        $messageMock->expects($this->once())
            ->method('setId')
            ->with(1);

        $messageMock->expects($this->once())
            ->method('setType')
            ->with('type');

        $this->automationDataFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($messageMock);

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->with(AutomationPublisher::TOPIC_SYNC_AUTOMATION, $messageMock);

        $this->automationPublisher->publish($automationMock);
    }

    public function testExceptionThrownForBadTopic()
    {
        $automationMock = $this->getMockBuilder(Automation::class)
            ->onlyMethods(['getId'])
            ->addMethods(['getAutomationType'])
            ->disableOriginalConstructor()
            ->getMock();

        $automationMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $automationMock->expects($this->once())
            ->method('getAutomationType')
            ->willReturn('type');

        $messageMock = $this->createMock(\Dotdigitalgroup\Email\Model\Queue\Data\AutomationData::class);
        $this->automationDataFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($messageMock);

        $messageMock->expects($this->once())
            ->method('setId')
            ->with(1);

        $messageMock->expects($this->once())
            ->method('setType')
            ->with('type');

        $this->publisherMock->expects($this->once())
            ->method('publish')
            ->willThrowException($e = new LocalizedException(__('Bad topic')));

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with('Automation publish failed');

        $this->automationPublisher->publish($automationMock);
    }
}
