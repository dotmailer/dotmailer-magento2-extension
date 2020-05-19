<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Campaign;

use Dotdigitalgroup\Email\Model\Monitor\AbstractMonitor;
use Dotdigitalgroup\Email\Model\Monitor\MonitorInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Campaign\CollectionFactory;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\FlagManager;

class Monitor extends AbstractMonitor implements MonitorInterface
{
    const MONITOR_ERROR_FLAG_CODE = 'ddg_monitor_campaign_errors';

    /**
     * @var CollectionFactory
     */
    protected $campaignCollection;

    /**
     * @var string
     */
    protected $monitorErrorFlagCode = self::MONITOR_ERROR_FLAG_CODE;

    /**
     * @var string
     */
    protected $typeName = 'campaign';

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
        $this->campaignCollection = $automationCollectionFactory;
        parent::__construct($flagManager, $scopeConfig);
    }

    /**
     * @param array $timeWindow
     * @return array
     * @throws Exception
     */
    public function fetchErrors(array $timeWindow)
    {
        return $this->campaignCollection->create()
            ->fetchCampaignsWithErrorStatusInTimeWindow($timeWindow)
            ->toArray();
    }

    /**
     * @param array $items
     * @return array
     */
    public function filterErrorItems(array $items)
    {
        $array = [];
        foreach ($items as $item) {
            if (!in_array($item['event_name'], $array)) {
                $array[] = $item['event_name'];
            }
        }

        return $array;
    }
}
