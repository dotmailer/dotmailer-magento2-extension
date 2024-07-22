<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Monitor;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Monitor\Automation\Monitor as AutomationMonitor;
use Dotdigitalgroup\Email\Model\Monitor\Campaign\Monitor as CampaignMonitor;
use Dotdigitalgroup\Email\Model\Monitor\Cron\Monitor as CronMonitor;
use Dotdigitalgroup\Email\Model\Monitor\EmailNotifier;
use Dotdigitalgroup\Email\Model\Monitor\Importer\Monitor as ImporterMonitor;
use Dotdigitalgroup\Email\Model\Monitor\Queue\Monitor as QueueMonitor;
use Dotdigitalgroup\Email\Model\Monitor\Smtp\Monitor as SmtpMonitor;
use Dotdigitalgroup\Email\Model\ResourceModel\User\Collection as UserCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Magento\Authorization\Model\ResourceModel\Role;
use Magento\Backend\Helper\Data as BackendData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailNotifierTest extends TestCase
{
    /**
     * @var UrlInterface|MockObject
     */
    private $urlInterfaceMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var Role|MockObject
     */
    private $roleMock;

    /**
     * @var TransportBuilder|MockObject
     */
    private $transportBuilderMock;

    /**
     * @var MockObject
     */
    private $userCollectionFactoryMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var Logger|MockObject
     */
    private $cronMonitorMock;

    /**
     * @var ImporterMonitor|MockObject
     */
    private $importerMonitorMock;

    /**
     * @var QueueMonitor|MockObject
     */
    private $queueMonitorMock;

    /**
     * @var FlagManager|MockObject
     */
    private $flagManagerMock;

    /**
     * @var BackendData|MockObject
     */
    private $backendDataMock;

    /**
     * @var CampaignMonitor|MockObject
     */
    private $campaignMonitorMock;

    /**
     * @var AutomationMonitor|MockObject
     */
    private $automationMonitorMock;

    /**
     * @var SmtpMonitor|MockObject
     */
    private $smtpMonitorMock;

    /**
     * @var Data|MockObject
     */
    private $dataMock;

    /**
     * @var EmailNotifier
     */
    private $emailNotifier;

    /**
     * @var UserCollection|MockObject
     */
    private $userCollectionMock;

    /**
     * @var Client|MockObject
     */
    private $clientMock;

    /**
     * @var EmailMessageInterface|MockObject
     */
    private $messageInterfaceMock;

    public function setUp(): void
    {
        $this->urlInterfaceMock = $this->createMock(UrlInterface::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->roleMock = $this->createMock(Role::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->cronMonitorMock = $this->createMock(CronMonitor::class);
        $this->importerMonitorMock = $this->createMock(ImporterMonitor::class);
        $this->queueMonitorMock = $this->createMock(QueueMonitor::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->backendDataMock = $this->createMock(BackendData::class);
        $this->campaignMonitorMock = $this->createMock(CampaignMonitor::class);
        $this->automationMonitorMock = $this->createMock(AutomationMonitor::class);
        $this->smtpMonitorMock = $this->createMock(SmtpMonitor::class);
        $this->dataMock = $this->createMock(Data::class);
        $this->clientMock = $this->createMock(Client::class);

        $this->messageInterfaceMock = $this->createMock(EmailMessageInterface::class);

        $this->userCollectionFactoryMock = $this->createMock(UserCollectionFactory::class);

        $this->userCollectionMock = $this->getMockBuilder(UserCollection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchUsersByRole'])
            ->getMock();

        $this->transportBuilderMock = $this->getMockBuilder(TransportBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'setTemplateIdentifier',
                    'setTemplateOptions',
                    'setTemplateVars',
                    'setFrom',
                    'addTo',
                    'getTransport',
                ]
            )
            ->addMethods(
                [
                    'sendMessage',
                    'getMessage',
                ]
            )
            ->getMock();

        $this->emailNotifier = new EmailNotifier(
            $this->urlInterfaceMock,
            $this->scopeConfigInterfaceMock,
            $this->roleMock,
            $this->transportBuilderMock,
            $this->userCollectionFactoryMock,
            $this->loggerMock,
            $this->cronMonitorMock,
            $this->importerMonitorMock,
            $this->flagManagerMock,
            $this->backendDataMock,
            $this->campaignMonitorMock,
            $this->automationMonitorMock,
            $this->queueMonitorMock,
            $this->smtpMonitorMock,
            $this->dataMock
        );
    }

    public function testThatIfEmailNotSentAndNoSmtpErrorsFoundMailWillBeSentViaTransportBuilder()
    {
        $this->setUpTransportBuilder();
        $this->setUpUserRecipient();

        $this->flagManagerMock->expects($this->once())
            ->method('getFlagData')
            ->with(EmailNotifier::MONITOR_EMAIL_SENT_FLAG_CODE)
            ->willReturn(false);

        $this->transportBuilderMock->expects($this->once())
            ->method('sendMessage');

        $this->emailNotifier->notify(
            ['from' => '01-01-2000 10:00:00', 'to' => '01-01-2001'],
            ['cron' =>['totalRecords' => 10, 'items' => []]]
        );
    }

    public function testThatIfSentTimeIsOlderThanFromTimeAndNoSmtpErrorMailWillBeSentViaTransportBuilder()
    {
        $this->setUpTransportBuilder();
        $this->setUpUserRecipient();

        $this->flagManagerMock->expects($this->once())
            ->method('getFlagData')
            ->with(EmailNotifier::MONITOR_EMAIL_SENT_FLAG_CODE)
            ->willReturn(strtotime('01-01-2000 9:00:00'));

        $this->transportBuilderMock->expects($this->once())
            ->method('sendMessage');

        $this->emailNotifier->notify(
            ['from' => strtotime('01-01-2000 10:00:00'), 'to' => strtotime('01-01-2000 11:00:00')],
            ['cron' =>['totalRecords' => 10, 'items' => []]]
        );
    }

    public function testThatIfSentTimeIsNewerThanFromTimeTransportBuilderWillNotSetUp()
    {
        $this->flagManagerMock->expects($this->once())
            ->method('getFlagData')
            ->with(EmailNotifier::MONITOR_EMAIL_SENT_FLAG_CODE)
            ->willReturn(strtotime('01-01-2000 10:30:00'));

        $this->transportBuilderMock->expects($this->never())
            ->method('sendMessage');

        $this->emailNotifier->notify(
            ['from' => strtotime('01-01-2000 10:00:00'), 'to' => strtotime('01-01-2000 11:00:00')],
            ['cron' =>['totalRecords' => 10, 'items' => []]]
        );
    }

    public function testThatIfEmailShouldBeSentAndSmtpErrorDetectedEmailWillBeSentViaApi()
    {
        $this->setWebApiClient();
        $this->setUpTransportBuilder();
        $this->setUpUserRecipient();

        $this->smtpMonitorMock->expects($this->once())
            ->method('filterErrorItems')
            ->willReturn(
                [
                'error1', 'error2', 'error3'
                ]
            );

        $this->flagManagerMock->expects($this->once())
            ->method('getFlagData')
            ->with(EmailNotifier::MONITOR_EMAIL_SENT_FLAG_CODE)
            ->willReturn(false);

        $this->transportBuilderMock->expects($this->never())
            ->method('sendMessage');

        $this->clientMock->expects($this->once())
            ->method('sendApiTransactionalEmail');

        $this->emailNotifier->notify(
            ['from' => '01-01-2000 10:00:00', 'to' => '01-01-2001'],
            [
                'cron' => ['totalRecords' => 10, 'items' => []],
                'smtp' => ['totalRecords' => 10, 'items' => []]
            ]
        );
    }

    private function setUpTransportBuilder()
    {
        $this->transportBuilderMock->expects($this->once())
            ->method('setTemplateIdentifier')
            ->willReturn($this->transportBuilderMock);

        $this->transportBuilderMock->expects($this->once())
            ->method('setTemplateVars')
            ->willReturn($this->transportBuilderMock);

        $this->transportBuilderMock->expects($this->once())
            ->method('setTemplateOptions')
            ->willReturn($this->transportBuilderMock);

        $this->transportBuilderMock->expects($this->once())
            ->method('setFrom')
            ->willReturn($this->transportBuilderMock);

        $this->transportBuilderMock->expects($this->once())
            ->method('addTo')
            ->willReturn($this->transportBuilderMock);

        $this->transportBuilderMock->expects($this->once())
            ->method('getTransport')
            ->willReturn($this->transportBuilderMock);

        $this->backendDataMock->expects($this->once())
            ->method('getHomePageUrl')
            ->willReturn('mychazstore.com');

        $this->cronMonitorMock->expects($this->once())
            ->method('filterErrorItems')
            ->willReturn(
                [
                'error1', 'error2', 'error3'
                ]
            );
    }

    private function setUpUserRecipient()
    {
        $userMock = $this->getMockBuilder(\Magento\User\Model\ResourceModel\User::class)
            ->disableOriginalConstructor()
            ->addMethods(['getEmail', 'getFirstName', 'getLastName'])
            ->getMock();

        $this->scopeConfigInterfaceMock->expects($this->atLeast(4))
            ->method('getValue');

        $this->userCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->userCollectionMock);

        $this->userCollectionMock->expects($this->once())
            ->method('fetchUsersByRole')
            ->willReturn([$userMock]);

        $userMock->expects($this->atLeastOnce())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $userMock->expects($this->once())
            ->method('getFirstName')
            ->willReturn('chaz');

        $userMock->expects($this->once())
            ->method('getLastName')
            ->willReturn('chaz');
    }

    private function setWebApiClient()
    {
        $this->dataMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->willReturn($this->clientMock);

        $this->transportBuilderMock->expects($this->atLeastOnce())
            ->method('getMessage')
            ->willReturn($this->messageInterfaceMock);

        $this->messageInterfaceMock->expects($this->once())
            ->method('getSubject')
            ->willReturn('myChazMail');

        $this->messageInterfaceMock->expects($this->once())
            ->method('getBodyText')
            ->willReturn('myBodyText');
    }
}
