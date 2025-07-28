<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Model\Monitor\AbstractMonitor;
use Dotdigitalgroup\Email\Model\Monitor\MonitorTypeProvider;
use Dotdigitalgroup\Email\Model\Monitor\AlertFrequency;
use Dotdigitalgroup\Email\Model\Monitor\EmailNotifier;
use Dotdigitalgroup\Email\Model\Task\TaskRunInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Monitor implements TaskRunInterface
{
    /**
     * @var MonitorTypeProvider
     */
    private $monitorTypeProvider;

    /**
     * @var AlertFrequency
     */
    private $alertFrequency;

    /**
     * @var EmailNotifier
     */
    private $emailNotifier;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Monitor constructor.
     * @param MonitorTypeProvider $monitorTypeProvider
     * @param AlertFrequency $alertFrequency
     * @param EmailNotifier $emailNotifier
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        MonitorTypeProvider $monitorTypeProvider,
        AlertFrequency $alertFrequency,
        EmailNotifier $emailNotifier,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->monitorTypeProvider = $monitorTypeProvider;
        $this->alertFrequency = $alertFrequency;
        $this->emailNotifier = $emailNotifier;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Run the Monitor task.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function run()
    {
        $ddgSystemMessagesEnabledInConfig = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_SYSTEM_MESSAGES
        );

        $ddgEmailNotificationsEnabledInConfig = $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYSTEM_ALERTS_EMAIL_NOTIFICATIONS
        );

        if (!$ddgSystemMessagesEnabledInConfig && !$ddgEmailNotificationsEnabledInConfig) {
            return;
        }

        $timeWindow = $this->alertFrequency->setTimeWindow();
        $errors = [];

        foreach ($this->monitorTypeProvider->getTypes() as $monitor) {
            /** @var AbstractMonitor $monitor */
            $monitorErrors = $monitor->fetchErrors($timeWindow);
            if ($ddgSystemMessagesEnabledInConfig) {
                $monitor->setSystemMessages($monitorErrors);
            }
            if (empty($monitorErrors['items'])) {
                continue;
            }

            foreach ($monitorErrors['items'] as $key => $items) {
                if (empty($items)) {
                    unset($monitorErrors['items'][$key]);
                }
            }

            $errors[$monitor->getTypeName()] = $monitorErrors;
        }

        if (!empty($errors) && $ddgEmailNotificationsEnabledInConfig) {
            $this->emailNotifier->notify($timeWindow, $errors);
        }
    }
}
