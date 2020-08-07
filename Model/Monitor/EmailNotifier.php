<?php

namespace Dotdigitalgroup\Email\Model\Monitor;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Monitor\Automation\Monitor as AutomationMonitor;
use Dotdigitalgroup\Email\Model\Monitor\Campaign\Monitor as CampaignMonitor;
use Dotdigitalgroup\Email\Model\Monitor\Cron\Monitor as CronMonitor;
use Dotdigitalgroup\Email\Model\Monitor\Importer\Monitor as ImporterMonitor;
use Dotdigitalgroup\Email\Model\Monitor\Smtp\Monitor as SmtpMonitor;
use Dotdigitalgroup\Email\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Magento\Authorization\Model\ResourceModel\Role;
use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Backend\Helper\Data as BackendData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\FlagManager;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\User\Model\ResourceModel\User\Collection as UserCollection;

class EmailNotifier
{
    const MONITOR_EMAIL_SENT_FLAG_CODE = 'ddg_monitor_email_sent';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Role
     */
    private $roleResource;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var UserCollectionFactory
     */
    private $userCollection;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CronMonitor
     */
    private $cronMonitor;

    /**
     * @var ImporterMonitor
     */
    private $importerMonitor;

    /**
     * @var AutomationMonitor
     */
    private $automationMonitor;

    /**
     * @var SmtpMonitor
     */
    private $smtpMonitor;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var CampaignMonitor
     */
    private $campaignMonitor;

    /**
     * @var BackendData
     */
    private $backendHelper;

    /**
     * @var Data
     */
    private $helper;

    /**
     * EmailNotifier constructor.
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param Role $roleResource
     * @param TransportBuilder $transportBuilder
     * @param UserCollectionFactory $userCollection
     * @param Logger $logger
     * @param CronMonitor $cronMonitor
     * @param ImporterMonitor $importerMonitor
     * @param FlagManager $flagManager
     * @param BackendData $backendHelper
     * @param CampaignMonitor $campaignMonitor
     * @param AutomationMonitor $automationMonitor
     * @param SmtpMonitor $smtpMonitor
     * @param Data $helper
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        Role $roleResource,
        TransportBuilder $transportBuilder,
        UserCollectionFactory $userCollection,
        Logger $logger,
        CronMonitor $cronMonitor,
        ImporterMonitor $importerMonitor,
        FlagManager $flagManager,
        BackendData $backendHelper,
        CampaignMonitor $campaignMonitor,
        AutomationMonitor $automationMonitor,
        SmtpMonitor $smtpMonitor,
        Data $helper
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->roleResource = $roleResource;
        $this->transportBuilder = $transportBuilder;
        $this->userCollection = $userCollection;
        $this->logger = $logger;
        $this->cronMonitor = $cronMonitor;
        $this->importerMonitor = $importerMonitor;
        $this->flagManager = $flagManager;
        $this->backendHelper = $backendHelper;
        $this->campaignMonitor = $campaignMonitor;
        $this->automationMonitor = $automationMonitor;
        $this->smtpMonitor = $smtpMonitor;
        $this->helper = $helper;
    }

    /**
     * @param array $timeWindow
     * @param array $errors
     * @throws LocalizedException
     */
    public function notify($timeWindow, $errors)
    {
        if ($this->canSendEmailNotification($timeWindow['from'])) {
            $this->sendNotifications($errors);
            $this->saveSentTime($timeWindow['to']);
        }
    }

    /**
     * @param array $errors
     * @throws LocalizedException
     */
    private function sendNotifications($errors)
    {
        $recipientCollection = $this->fetchContactsFromSelectedRoles();

        foreach ($recipientCollection as $recipient) {
            $transport = $this->transportBuilder->setTemplateIdentifier(
                $this->scopeConfig->getValue(
                    Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_EMAIL_NOTIFICATION_TEMPLATE
                )
            )->setTemplateOptions([
                'area' => FrontNameResolver::AREA_CODE,
                'store' => Store::DEFAULT_STORE_ID
            ])->setTemplateVars(
                $this->gatherTemplateVars($recipient, $errors)
            )->setFrom([
                'name' => $this->scopeConfig->getValue('trans_email/ident_general/name'),
                'email' => $this->scopeConfig->getValue('trans_email/ident_general/email')
            ])->addTo(
                $recipient->getEmail(),
                $recipient->getFirstName() . ' ' . $recipient->getLastName()
            )->getTransport();

            if (isset($errors['smtp'])) {
                //API METHOD TO SEND MAILS
                $client = $this->helper->getWebsiteApiClient();

                $content = [
                    "Subject" => $transport->getMessage()->getSubject(),
                    "ToAddresses" => [$recipient->getEmail()],
                    "FromAddress" => $this->scopeConfig->getValue('trans_email/ident_general/email'),
                    "HtmlContent" => quoted_printable_decode($transport->getMessage()->getBodyText())
                ];

                $client->sendApiTransactionalEmail($content);
            } else {
                $transport->sendMessage();
            }
        }
    }

    /**
     * Determine if the stored flag time (i.e. the last time we sent an email notification)
     * is older than the start of the $timeWindow e.g. 24 hours ago if Alert Frequency = 24 Hours
     *
     * @param string $sinceTime
     * @return bool
     */
    private function canSendEmailNotification($sinceTime)
    {
        $flagTime = $this->flagManager->getFlagData(self::MONITOR_EMAIL_SENT_FLAG_CODE);

        if (!$flagTime) {
            return true;
        }

        return $flagTime < $sinceTime;
    }

    /**
     * @param string $time
     */
    private function saveSentTime($time)
    {
        $this->flagManager->saveFlag(self::MONITOR_EMAIL_SENT_FLAG_CODE, $time);
    }

    /**
     * @return UserCollection
     */
    private function fetchContactsFromSelectedRoles()
    {
        $selectedRoles = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_USER_ROLES
        );

        return $this->userCollection->create()
            ->fetchUsersByRole($selectedRoles);
    }

    /**
     * @param \Magento\User\Model\User $recipient
     * @param array $data
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function gatherTemplateVars($recipient, $data)
    {
        $templateVars = [
            'user' => $recipient,
            'host' => 'host',
            'store_url' => $this->backendHelper->getHomePageUrl(),
        ];

        foreach ($data as $area => $errors) {
            $templateVars[$area . '_total'] = $errors['totalRecords'];
            $templateVars[$area . '_items'] = $errors['items'];
            $templateVars[$area . '_summary'] = implode(
                ', ',
                $this->{$area . 'Monitor'}->filterErrorItems($errors['items'])
            );
        }

        return $templateVars;
    }
}
