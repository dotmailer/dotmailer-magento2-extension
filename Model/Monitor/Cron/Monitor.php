<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Cron;

use Dotdigitalgroup\Email\Model\Monitor\MonitorInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Cron\CollectionFactory;
use Magento\Framework\FlagManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Dotdigitalgroup\Email\Model\System\Message\CronError;

class Monitor implements MonitorInterface
{
    /**
     * Flag code for cron status.
     */
    const CRON_MONITOR_ERROR_FLAG_CODE = 'ddg_monitor_cron_errors';

    /**
     * @var CollectionFactory
     */
    private $cronCollection;

    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * CronMonitor constructor.
     * @param CollectionFactory $cronCollectionFactory
     * @param FlagManager $flagManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CollectionFactory $cronCollectionFactory,
        FlagManager $flagManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->cronCollection = $cronCollectionFactory;
        $this->flagManager = $flagManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return void
     */
    public function run()
    {
        $ddgSystemMessagesEnabledInConfig = $this->scopeConfig->getValue(
            CronError::XML_PATH_CONNECTOR_SYSTEM_MESSAGES
        );

        if (!$ddgSystemMessagesEnabledInConfig) {
            return;
        }

        $cronsWithErrors = $this->fetchCronTasksWithErrorStatus();
        if (count($cronsWithErrors) > 0) {
            $this->flagManager->saveFlag(self::CRON_MONITOR_ERROR_FLAG_CODE, $cronsWithErrors);
        } else {
            $this->flagManager->deleteFlag(self::CRON_MONITOR_ERROR_FLAG_CODE);
        }
    }

    /**
     * @return array
     */
    private function fetchCronTasksWithErrorStatus()
    {
        $rowsWithError = $this->cronCollection->create()
            ->addFieldToFilter('job_code', ['like' => "%ddg_automation%"])
            ->addFieldToFilter('status', 'error')
            ->getColumnValues('job_code');

        return array_unique($rowsWithError);
    }

}

