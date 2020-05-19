<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Automation;

use Dotdigitalgroup\Email\Model\Monitor\AbstractMonitor;
use Dotdigitalgroup\Email\Model\Monitor\MonitorInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Automation\CollectionFactory;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;

class Monitor extends AbstractMonitor implements MonitorInterface
{
    const MONITOR_ERROR_FLAG_CODE = 'ddg_monitor_automation_errors';

    /**
     * @var CollectionFactory
     */
    protected $automationCollection;

    /**
     * @var string
     */
    protected $monitorErrorFlagCode = self::MONITOR_ERROR_FLAG_CODE;

    /**
     * @var string
     */
    protected $typeName = 'automation';

    /**
     * @param FlagManager $flagManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $automationCollectionFactory
     */
    public function __construct(
        FlagManager $flagManager,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $automationCollectionFactory
    ) {
        $this->flagManager = $flagManager;
        $this->scopeConfig = $scopeConfig;
        $this->automationCollection = $automationCollectionFactory;
        parent::__construct($flagManager, $scopeConfig);
    }

    /**
     * @param array $timeWindow
     * @return array
     * @throws Exception
     */
    public function fetchErrors(array $timeWindow)
    {
        $automationErrorCollection = $this->automationCollection->create()
            ->fetchAutomationEnrolmentsWithErrorStatusInTimeWindow($timeWindow);

        $automationPendingCollection = $this->automationCollection->create()
            ->fetchAutomationEnrolmentsWithPendingStatusInTimeWindow($timeWindow);

        $items = array_merge($automationErrorCollection->getItems(), $automationPendingCollection->getItems());

        return [
            'items' => $items,
            'totalRecords' => count($items)
        ];
    }

    /**
     * @param array $items
     * @return array
     */
    public function filterErrorItems(array $items)
    {
        $array = [];
        foreach ($items as $item) {
            if (!in_array($item['automation_type'], $array)) {
                $array[] = $item['automation_type'];
            }
        }

        return $array;
    }
}
