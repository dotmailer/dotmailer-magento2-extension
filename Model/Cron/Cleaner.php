<?php

namespace Dotdigitalgroup\Email\Model\Cron;

use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Model\Cron\JobChecker;
use Dotdigitalgroup\Email\Model\DateIntervalFactory;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Intl\DateTimeFactory;

class Cleaner
{
    /**
     * @var File
     */
    private $fileHelper;

    /**
     * @var JobChecker
     */
    private $jobChecker;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var DateIntervalFactory
     */
    private $dateIntervalFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var array
     */
    private $tables = [
        'automation' => SchemaInterface::EMAIL_AUTOMATION_TABLE,
        'importer' => SchemaInterface::EMAIL_IMPORTER_TABLE,
        'campaign' => SchemaInterface::EMAIL_CAMPAIGN_TABLE,
    ];

    /**
     * Cleaner constructor.
     *
     * @param File $fileHelper
     * @param JobChecker $jobChecker
     * @param DateTimeFactory $dateTimeFactory
     * @param DateIntervalFactory $dateIntervalFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        File $fileHelper,
        JobChecker $jobChecker,
        DateTimeFactory $dateTimeFactory,
        DateIntervalFactory $dateIntervalFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->fileHelper = $fileHelper;
        $this->jobChecker = $jobChecker;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->dateIntervalFactory = $dateIntervalFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Cleaning for csv files and connector tables.
     */
    public function cleanUp()
    {
        if ($this->jobChecker->hasAlreadyBeenRun('ddg_automation_cleaner')) {
            return;
        }

        $tables = $this->getTablesForCleanUp();

        foreach ($tables as $key => $table) {
            $this->cleanTable($table);
        }

        $archivedFolder = $this->fileHelper->getArchiveFolder();
        $this->fileHelper->deleteDir($archivedFolder);
    }

    /**
     * @param $additionalTables
     * @return array
     */
    public function getTablesForCleanUp(array $additionalTables = [])
    {
        return $this->tables + $additionalTables;
    }

    /**
     * Delete records older than 30 days from the provided table.
     *
     * @param string $tableName
     *
     * @return \Exception|int
     */
    private function cleanTable($tableName)
    {
        try {
            $now = $this->dateTimeFactory->create('now', new \DateTimeZone('UTC'));
            $interval = $this->dateIntervalFactory->create(['interval_spec' => 'P30D']);
            $date = $now->sub($interval)->format('Y-m-d H:i:s');
            $conn = $this->resourceConnection->getConnection();
            $num = $conn->delete(
                $this->resourceConnection->getTableName($tableName),
                ['created_at < ?' => $date]
            );

            return $num;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
