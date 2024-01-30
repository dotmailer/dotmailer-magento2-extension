<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Queue\Sync\Automation;

use Dotdigitalgroup\Email\Model\Queue\Sync\Automation\AutomationPublisher;
use Dotdigitalgroup\Email\Model\Automation;
use Dotdigitalgroup\Email\Model\Queue\Data\AutomationDataFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use PHPUnit\Framework\TestCase;

class AutomationPublisherTest extends TestCase
{
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
        $this->automationDataFactoryMock = $this->getMockBuilder(AutomationDataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->publisherMock = $this->getMockBuilder(PublisherInterface::class)
            ->getMock();

        $this->automationPublisher = new AutomationPublisher(
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

        $messageMock = $this->getMockBuilder(\Dotdigitalgroup\Email\Model\Queue\Data\AutomationData::class)
            ->disableOriginalConstructor()
            ->getMock();

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
            ->with('ddg.sync.automation', $messageMock);

        $this->automationPublisher->publish($automationMock);
    }
}
