<?php

namespace Dotdigitalgroup\Email\Model\Monitor\Smtp;

use Dotdigitalgroup\Email\Model\Monitor\AbstractMonitor;
use Dotdigitalgroup\Email\Model\Monitor\MonitorInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory;
use Magento\Framework\FlagManager;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Monitor extends AbstractMonitor implements MonitorInterface
{
    public const MONITOR_ERROR_FLAG_CODE = 'ddg_monitor_smtp_errors';
    public const SMTP_ERROR_FLAG_CODE = 'ddg_smtp_errors';

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
    protected $typeName = 'smtp';

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
        $errors = $this->flagManager->getFlagData(self::SMTP_ERROR_FLAG_CODE) ?? [];
        $filteredErrors = $this->filterDateRangeErrors($timeWindow, $errors);

        if ($filteredErrors) {
            $this->flagManager->saveFlag(self::SMTP_ERROR_FLAG_CODE, $filteredErrors);
        } else {
            $this->flagManager->deleteFlag(self::SMTP_ERROR_FLAG_CODE);
        }

        return [
            'items' => $filteredErrors,
            'totalRecords' => count($filteredErrors)
        ];
    }

    /**
     * Filter error items.
     *
     * @param array $items
     * @return array
     */
    public function filterErrorItems(array $items)
    {
        $array = [];
        foreach ($items as $item) {
            if (!in_array($item['error_message'], $array)) {
                $array[] = $item['error_message'];
            }
        }

        return $array;
    }

    /**
     * Filter errors by date range.
     *
     * @param array $timeWindow
     * @param array $errors
     * @return mixed
     */
    private function filterDateRangeErrors($timeWindow, $errors = [])
    {
        foreach ($errors as $key => $error) {
            if ($error['date'] >= $timeWindow['from'] && $error['date'] <= $timeWindow['to']) {
                continue;
            }
            unset($errors[$key]);
        }
        return $errors;
    }
}
