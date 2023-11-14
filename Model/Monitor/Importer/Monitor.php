<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Importer;

use Dotdigitalgroup\Email\Model\Monitor\AbstractMonitor;
use Dotdigitalgroup\Email\Model\Monitor\MonitorInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory;
use Magento\Framework\FlagManager;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Monitor extends AbstractMonitor implements MonitorInterface
{
    public const MONITOR_ERROR_FLAG_CODE = 'ddg_monitor_importer_errors';

    /**
     * @var CollectionFactory
     */
    private $importerCollection;

    /**
     * @var string
     */
    protected $monitorErrorFlagCode = self::MONITOR_ERROR_FLAG_CODE;

    /**
     * @var string
     */
    protected $typeName = 'importer';

    /**
     * Monitor constructor.
     *
     * @param FlagManager $flagManager
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $importerCollectionFactory
     */
    public function __construct(
        FlagManager $flagManager,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $importerCollectionFactory
    ) {
        $this->flagManager = $flagManager;
        $this->scopeConfig = $scopeConfig;
        $this->importerCollection = $importerCollectionFactory;
        parent::__construct($flagManager, $scopeConfig);
    }

    /**
     * Fetch errors for the given time window.
     *
     * @param array $timeWindow
     * @return array
     * @throws \Exception
     */
    public function fetchErrors(array $timeWindow)
    {
        return $this->importerCollection->create()
            ->fetchImporterTasksWithErrorStatusInTimeWindow($timeWindow)
            ->toArray();
    }

    /**
     * Filter the errors by date range.
     *
     * @param array $items
     * @return array
     */
    public function filterErrorItems(array $items)
    {
        $array = [];
        foreach ($items as $item) {
            $itemType = $item['import_type'];
            if (strpos($itemType, 'Catalog_') !== false) {
                $itemType = 'Catalog';
            }
            if (!in_array($itemType, $array)) {
                $array[] = $itemType;
            }
        }

        return $array;
    }
}
