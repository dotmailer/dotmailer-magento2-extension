<?php

namespace Dotdigitalgroup\Email\Setup\Install;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Setup\Install\Type\AbstractDataMigration;
use Magento\Framework\Math\Random;
use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DataMigrationHelper
{
    /**
     * @var ConfigResource
     */
    private $config;

    /**
     * @var Random
     */
    private $randomMath;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Console output interface
     * @var OutputInterface
     */
    private $output;

    /**
     * @var DataMigrationTypeProvider
     */
    private $dataMigrationTypeProvider;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ConfigResource $config
     * @param Random $random
     * @param ResourceConnection $resourceConnection
     * @param Logger $logger
     * @param DataMigrationTypeProvider $dataMigrationTypeProvider
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ConfigResource $config,
        Random $random,
        ResourceConnection $resourceConnection,
        Logger $logger,
        DataMigrationTypeProvider $dataMigrationTypeProvider,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->config = $config;
        $this->randomMath = $random;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
        $this->dataMigrationTypeProvider = $dataMigrationTypeProvider;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Run.
     *
     * @param string|null $table
     */
    public function run(?string $table = null)
    {
        $types = $this->dataMigrationTypeProvider->getEnabledTypes($table);

        // loop through types and execute
        foreach ($types as $dataMigration) {
            try {
                /** @var AbstractDataMigration $dataMigration */
                $dataMigration->execute();
                $this->logActions($dataMigration);
            } catch (\Exception $e) {
                $this->logErrors($dataMigration, $e->getMessage());
            }
        }
    }

    /**
     * Set an output interface for logging to the console.
     *
     * @param OutputInterface $output
     * @return $this
     */
    public function setOutputInterface(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * Get tables from types.
     *
     * @return array
     */
    public function getTablesFromAvailableTypes()
    {
        $tables = [];
        foreach ($this->dataMigrationTypeProvider->getTypes() as $migrationType) {
            $tables[] = $migrationType->getTableName();
        }

        return array_unique($tables);
    }

    /**
     * Truncate relevant tables before running.
     *
     * @param string|null $table
     *
     * @return $this
     */
    public function emptyTables(?string $table = null)
    {
        $tablesProcessed = [];
        $connection = $this->resourceConnection->getConnection();
        $connection->query('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($this->dataMigrationTypeProvider->getEnabledTypes($table) as $migrationType) {
            /** @var AbstractDataMigration $migrationType */
            $tableName = $this->resourceConnection->getTableName($migrationType->getTableName());

            if (in_array($tableName, $tablesProcessed)) {
                continue;
            }

            if (!$connection->isTableExists($tableName)) {
                continue;
            }

            $connection->query(sprintf('TRUNCATE TABLE %s', $tableName));

            $tablesProcessed[] = $tableName;
        }

        $connection->query('SET FOREIGN_KEY_CHECKS = 1');

        return $this;
    }

    /**
     * Generate a random string and save in config.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateAndSaveCode()
    {
        $passcode = $this->scopeConfig->getValue(
            \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE
        );

        if (!$passcode) {
            $code = $this->randomMath->getRandomString(32);
            $this->config->saveConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_DYNAMIC_CONTENT_PASSCODE,
                $code,
                'default',
                '0'
            );
        }
    }

    /**
     * Log actions of each data migration.
     *
     * @param AbstractDataMigration $dataMigration
     */
    public function logActions(AbstractDataMigration $dataMigration)
    {
        $this->logger->debug('Dotdigitalgroup_Email data installer', [
            'type' => get_class($dataMigration),
            'rows_affected' => $dataMigration->getRowsAffected(),
        ]);

        if ($this->output) {
            $this->output->writeln(sprintf(
                '%s: rows affected %s',
                get_class($dataMigration),
                $dataMigration->getRowsAffected()
            ));
        }
    }

    /**
     * Log errors from a data migration.
     *
     * @param AbstractDataMigration $dataMigration
     * @param string $exceptionMessage
     */
    public function logErrors(AbstractDataMigration $dataMigration, string $exceptionMessage)
    {
        $this->logger->error('Dotdigitalgroup_Email data installer', [
            'type' => get_class($dataMigration),
            'error' => $exceptionMessage,
        ]);

        if ($this->output) {
            $this->output->writeln(sprintf(
                'ERROR [%s]: %s',
                get_class($dataMigration),
                $exceptionMessage
            ));
        }
    }
}
