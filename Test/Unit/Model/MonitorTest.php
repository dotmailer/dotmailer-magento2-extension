<?php
namespace Dotdigitalgroup\Email\Test\Unit\Model;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Monitor;
use Dotdigitalgroup\Email\Model\Monitor\AlertFrequency;
use Dotdigitalgroup\Email\Model\Monitor\Cron\Monitor as CronMonitor;
use Dotdigitalgroup\Email\Model\Monitor\EmailNotifier;
use Dotdigitalgroup\Email\Model\Monitor\MonitorTypeProvider;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class MonitorTest extends TestCase
{
    /**
     * @var Monitor
     */
    private $monitor;

    /**
     * @var MonitorTypeProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $monitorTypeProviderMock;

    /**
     * @var AlertFrequency|\PHPUnit\Framework\MockObject\MockObject
     */
    private $alertFrequencyMock;

    /**
     * @var EmailNotifier|\PHPUnit\Framework\MockObject\MockObject
     */
    private $emailNotifierMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var CronMonitor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cronMonitorMock;

    protected function setUp(): void
    {
        $this->monitorTypeProviderMock = $this->createMock(MonitorTypeProvider::class);
        $this->alertFrequencyMock = $this->createMock(AlertFrequency::class);
        $this->emailNotifierMock = $this->createMock(EmailNotifier::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->cronMonitorMock = $this->createMock(CronMonitor::class);

        $this->monitor = new Monitor(
            $this->monitorTypeProviderMock,
            $this->alertFrequencyMock,
            $this->emailNotifierMock,
            $this->scopeConfigInterfaceMock
        );
    }

    public function testThatIfAlertsEnabledAndIfErrorsNotFoundEmailWillNotBeTriggered()
    {
        $this->setDdgMonitorMessagesAndEmailsEnabled();
        $this->cronMonitorMock->expects($this->once())
            ->method('fetchErrors')
            ->willReturn([]);

        $this->cronMonitorMock->expects($this->once())
            ->method('setSystemMessages');

        $this->emailNotifierMock->expects($this->never())
            ->method('notify');

        $this->monitor->run();
    }

    public function testThatIfAlertsEnabledAndIfErrorsFoundEmailWillBeTriggered()
    {
        $this->setDdgMonitorMessagesAndEmailsEnabled();
        $this->cronMonitorMock->expects($this->once())
            ->method('fetchErrors')
            ->willReturn(['items' => 'errorsFound']);

        $this->cronMonitorMock->expects($this->once())
            ->method('setSystemMessages');

        $this->emailNotifierMock->expects($this->exactly(1))
            ->method('notify');

        $this->monitor->run();
    }

    public function testThatIfAlertsDisabledEmailAndSystemMessagesWillNotBeTriggered()
    {
        $this->setDdgMonitorMessagesAndEmailsDisabled();

        $this->alertFrequencyMock->expects($this->never())
            ->method('setTimeWindow');

        $this->monitorTypeProviderMock->expects($this->never())
            ->method('getTypes');

        $this->cronMonitorMock->expects($this->never())
            ->method('fetchErrors');

        $this->cronMonitorMock->expects($this->never())
            ->method('setSystemMessages');

        $this->emailNotifierMock->expects($this->never())
            ->method('notify');

        $this->monitor->run();
    }

    public function testThatIfEmailsAreDisabledMessagesAreEnabledAndIfErrorsExistMailWillNotBeTriggered()
    {
        $this->setDdgMonitorMessagesEnabledAndEmailsDIsabled();

        $this->cronMonitorMock->expects($this->once())
            ->method('fetchErrors')
            ->willReturn(['items' => 'errorsFound']);

        $this->cronMonitorMock->expects($this->once())
            ->method('setSystemMessages');

        $this->emailNotifierMock->expects($this->never())
            ->method('notify');

        $this->monitor->run();
    }

    private function setDdgMonitorMessagesAndEmailsEnabled()
    {
        $this->scopeConfigInterfaceMock->expects($this->at(0))
            ->method('getValue')
            ->with(Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_SYSTEM_MESSAGES)
            ->willReturn(1);

        $this->scopeConfigInterfaceMock->expects($this->at(1))
            ->method('getValue')
            ->with(Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_EMAIL_NOTIFICATIONS)
            ->willReturn(1);

        $this->setTimeWindowAndTypes();
    }

    private function setDdgMonitorMessagesAndEmailsDisabled()
    {
        $this->scopeConfigInterfaceMock->expects($this->at(0))
            ->method('getValue')
            ->with(Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_SYSTEM_MESSAGES)
            ->willReturn(0);

        $this->scopeConfigInterfaceMock->expects($this->at(1))
            ->method('getValue')
            ->with(Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_EMAIL_NOTIFICATIONS)
            ->willReturn(0);
    }

    private function setDdgMonitorMessagesEnabledAndEmailsDisabled()
    {
        $this->scopeConfigInterfaceMock->expects($this->at(0))
            ->method('getValue')
            ->with(Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_SYSTEM_MESSAGES)
            ->willReturn(1);

        $this->scopeConfigInterfaceMock->expects($this->at(1))
            ->method('getValue')
            ->with(Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_EMAIL_NOTIFICATIONS)
            ->willReturn(0);

        $this->setTimeWindowAndTypes();
    }
    private function setTimeWindowAndTypes()
    {
        $this->alertFrequencyMock->expects($this->once())
            ->method('setTimeWindow')
            ->willReturn(
                [
                    'from' => 'from',
                    'to' => 'to'
                ]
            );

        $this->monitorTypeProviderMock->expects($this->once())
            ->method('getTypes')
            ->willReturn([$this->cronMonitorMock]);
    }
}
