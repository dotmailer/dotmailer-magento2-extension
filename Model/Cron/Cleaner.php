<?php

namespace Dotdigitalgroup\Email\Model\Cron;

use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Setup\SchemaInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Task\TaskRunInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Cleaner implements TaskRunInterface
{
    private const BATCH_SIZE = 10000;

    /**
     * @var File
     */
    private $fileHelper;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private $tables = [
        'automation' => SchemaInterface::EMAIL_AUTOMATION_TABLE,
        'importer' => SchemaInterface::EMAIL_IMPORTER_TABLE,
        'campaign' => SchemaInterface::EMAIL_CAMPAIGN_TABLE,
        'consent' => SchemaInterface::EMAIL_CONTACT_CONSENT_TABLE
    ];

    /**
     * Cleaner constructor.
     *
     * @param File $fileHelper
     * @param DateTimeFactory $dateTimeFactory
     * @param ResourceConnection $resourceConnection
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        File $fileHelper,
        DateTimeFactory $dateTimeFactory,
        ResourceConnection $resourceConnection,
        Logger $logger,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->fileHelper = $fileHelper;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Cleaning for csv files and connector tables.
     *
     * @return void
     * @throws FileSystemException
     */
    public function run(): void
    {
        $tables = $this->getTablesForCleanUp();

        foreach ($tables as $table) {
            $dateColumn = $table === SchemaInterface::EMAIL_CONTACT_CONSENT_TABLE ?
                'consent_datetime' :
                'created_at';
            $this->cleanTable($table, $dateColumn);
        }

        $this->cleanUpCsvArchiveFolder();
    }

    /**
     * Get tables for cleanup.
     *
     * @param array $additionalTables
     *
     * @return array
     */
    public function getTablesForCleanUp(array $additionalTables = [])
    {
        return $this->tables + $additionalTables;
    }

    /**
     * Delete records older than x days from the provided table.
     *
     * @param string $tableName
     * @param string $dateColumn
     *
     * @return void
     */
    private function cleanTable(string $tableName, string $dateColumn)
    {
        try {
            $now = $this->dateTimeFactory->create('now', new \DateTimeZone('UTC'));
            $interval = new \DateInterval(sprintf('P%sD', $this->getTableCleanerInterval()));
            $date = $now->sub($interval)->format('Y-m-d H:i:s');
            $conn = $this->resourceConnection->getConnection();

            $numRows = 0;
            while (true) {
                $select = $conn->select()
                    ->from(
                        ['table_to_clean' => $this->resourceConnection->getTableName($tableName)],
                        ['table_to_clean.id']
                    )->where(
                        $conn->quoteInto($dateColumn . ' < ?', $date)
                    )->limit(self::BATCH_SIZE);

                $ids = $conn->fetchCol($select);

                if (empty($ids)) {
                    break;
                }

                $numRows += $conn->delete(
                    $this->resourceConnection->getTableName($tableName),
                    ['id IN (?)' => $ids]
                );
            }
            $this->logger->info(
                sprintf('Cleaner: deleted %s rows from %s.', $numRows, $tableName)
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'An error occurred when cleaning database tables.',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Get table cleaner interval.
     *
     * @return string
     */
    public function getTableCleanerInterval(): string
    {
        return (string) $this->scopeConfig->getValue(Config::XML_PATH_CRON_SCHEDULE_TABLE_CLEANER_INTERVAL);
    }

    /**
     * Clean up CSV archive folder.
     *
     * @return void
     * @throws FileSystemException
     *
     * @deprecated CSV files are no longer used.
     * @see \Dotdigitalgroup\Email\Model\Sync\Importer\Type\Contact\BulkJson;
     */
    private function cleanUpCsvArchiveFolder()
    {
        $archivedFolder = $this->fileHelper->getArchiveFolder();
        $this->fileHelper->deleteDir($archivedFolder);
    }
}
