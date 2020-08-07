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
use Dotdigitalgroup\Email\Model\Monitor\Smtp\Monitor as SmtpMonitor;
use Dotdigitalgroup\Email\Model\ResourceModel\User\Collection as UserCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Magento\Authorization\Model\ResourceModel\Role;
use Magento\Backend\Helper\Data as BackendData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

class EmailNotifierTest extends TestCase
{
    /**
     * @var UrlInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlInterfaceMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var Role|\PHPUnit\Framework\MockObject\MockObject
     */
    private $roleMock;

    /**
     * @var TransportBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transportBuilderMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $userCollectionFactoryMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cronMonitorMock;

    /**
     * @var ImporterMonitor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $importerMonitorLog;

    /**
     * @var FlagManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $flagManagerMock;

    /**
     * @var BackendData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $backendDataMock;

    /**
     * @var CampaignMonitor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $campaignMonitorMock;

    /**
     * @var AutomationMonitor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $automationMonitorMock;

    /**
     * @var SmtpMonitor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $smtpMonitorMock;

    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dataMock;

    /**
     * @var EmailNotifier
     */
    private $emailNotifier;

    /**
     * @var UserCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userCollectionMock;

    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $clientMock;

    /**
     * @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageInterfaceMock;

    public function setUp(): void
    {
        $this->urlInterfaceMock = $this->createMock(UrlInterface::class);
        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);
        $this->roleMock = $this->createMock(Role::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->cronMonitorMock = $this->createMock(CronMonitor::class);
        $this->importerMonitorLog = $this->createMock(ImporterMonitor::class);
        $this->flagManagerMock = $this->createMock(FlagManager::class);
        $this->backendDataMock = $this->createMock(BackendData::class);
        $this->campaignMonitorMock = $this->createMock(CampaignMonitor::class);
        $this->automationMonitorMock = $this->createMock(AutomationMonitor::class);
        $this->smtpMonitorMock = $this->createMock(SmtpMonitor::class);
        $this->dataMock = $this->createMock(Data::class);
        $this->clientMock = $this->createMock(Client::class);

        $this->messageInterfaceMock = $this->getMockBuilder(MessageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(array_merge(get_class_methods(MessageInterface::class), ['getBodyText','getMessage']))
            ->getMock();

        $this->userCollectionFactoryMock = $this->getMockBuilder(UserCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','fetchUsersByRole'])
            ->getMock();

        $this->userCollectionMock = $this->getMockBuilder(UserCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEmail','getFirstName','getLastName'])
            ->getMock();

        $this->transportBuilderMock = $this->getMockBuilder(TransportBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'sendMessage',
                    'setTemplateIdentifier',
                    'setTemplateOptions',
                    'setTemplateVars',
                    'setFrom',
                    'addTo',
                    'getTransport',
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
            $this->importerMonitorLog,
            $this->flagManagerMock,
            $this->backendDataMock,
            $this->campaignMonitorMock,
            $this->automationMonitorMock,
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
            ->willReturn([
                'error1', 'error2', 'error3'
            ]);

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
            ->willReturn([
                'error1', 'error2', 'error3'
            ]);
    }

    private function setUpUserRecipient()
    {
        $this->scopeConfigInterfaceMock->expects($this->at(0))
            ->method('getValue')
            ->with(Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_USER_ROLES);

        $this->scopeConfigInterfaceMock->expects($this->at(1))
            ->method('getValue')
            ->with(Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_EMAIL_NOTIFICATION_TEMPLATE);

        $this->scopeConfigInterfaceMock->expects($this->at(2))
            ->method('getValue')
            ->with('trans_email/ident_general/name');

        $this->scopeConfigInterfaceMock->expects($this->at(3))
            ->method('getValue')
            ->with('trans_email/ident_general/email');

        $this->userCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->userCollectionFactoryMock);

        $this->userCollectionFactoryMock->expects($this->once())
            ->method('fetchUsersByRole')
            ->willReturn([$this->userCollectionMock]);

        $this->userCollectionMock->expects($this->atLeastOnce())
            ->method('getEmail')
            ->willReturn('chaz@emailsim.io');

        $this->userCollectionMock->expects($this->once())
            ->method('getFirstName')
            ->willReturn('chaz');

        $this->userCollectionMock->expects($this->once())
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
