<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Cron;

use Dotdigitalgroup\Email\Model\Monitor\AbstractMonitor;
use Dotdigitalgroup\Email\Model\Monitor\MonitorInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Cron\CollectionFactory;
use Magento\Framework\FlagManager;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Monitor extends AbstractMonitor implements MonitorInterface
{
    public const MONITOR_ERROR_FLAG_CODE = 'ddg_monitor_cron_errors';

    /**
     * @var CollectionFactory
     */
    private $cronCollection;

    /**
     * @var string
     */
    protected $monitorErrorFlagCode = self::MONITOR_ERROR_FLAG_CODE;

    /**
     * @var string
     */
    protected $typeName = 'cron';

    /**
     * Monitor constructor.
     *
     * @param FlagManager $flagManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $cronCollectionFactory
     */
    public function __construct(
        FlagManager $flagManager,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $cronCollectionFactory
    ) {
        $this->flagManager = $flagManager;
        $this->scopeConfig = $scopeConfig;
        $this->cronCollection = $cronCollectionFactory;
        parent::__construct($flagManager, $scopeConfig);
    }

    /**
     * Fetch errors for the given time window.
     *
     * @param array $timeWindow
     * @return array
     */
    public function fetchErrors(array $timeWindow)
    {
        return $this->cronCollection->create()
            ->fetchCronTasksWithErrorStatusInTimeWindow($timeWindow)
            ->toArray();
    }

    /**
     * Filter the error items.
     *
     * @param array $items
     * @return array
     */
    public function filterErrorItems(array $items)
    {
        $array = [];
        foreach ($items as $item) {
            if (!in_array($item['job_code'], $array)) {
                $array[] = $item['job_code'];
            }
        }

        return $array;
    }
}
