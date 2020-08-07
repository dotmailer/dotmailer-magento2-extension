<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Monitor\Automation;

use Dotdigitalgroup\Email\Model\Monitor\Automation\Monitor as AutomationMonitor;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;
use PHPUnit\Framework\TestCase;

class AutomationMonitorTest extends TestCase
{
    /**
     * @var FlagManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $flagManagerMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var AutomationMonitor
     */
    private $automationMonitor;

    /**
     * @var CollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionFactoryMock;

    public function setUp(): void
    {
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->automationMonitor = new AutomationMonitor(
            $this->flagManagerMock,
            $this->scopeConfigInterfaceMock,
            $this->collectionFactoryMock
        );
    }

    public function testIfErrorsFoundFlagWillNotDeleteAndWillBeSaved()
    {
        $this->flagManagerMock->expects($this->never())
            ->method('deleteFlag');

        $this->flagManagerMock->expects($this->once())
            ->method('saveFlag');

        $this->automationMonitor->setSystemMessages(['items' => [['automation_type' => 'ReviewAutomation']]]);
    }

    public function testIfErrorsNotFoundFlagWillDeleteAndWillNotBeSaved()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('deleteFlag');

        $this->flagManagerMock->expects($this->never())
            ->method('saveFlag');

        $this->automationMonitor->setSystemMessages(['items' => []]);
    }
}
