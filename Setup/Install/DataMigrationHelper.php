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
     * Run all install methods
     */
    public function run()
    {
        // truncate any tables which are about to be updated
        $this->emptyTables();

        // loop through types and execute
        foreach ($this->dataMigrationTypeProvider->getTypes() as $dataMigration) {
            /** @var AbstractDataMigration $dataMigration */
            $dataMigration->execute();
            $this->logActions($dataMigration);
        }

        /**
         * Save config value
         */
        $this->generateAndSaveCode();
    }

    /**
     * Set an output interface for logging to the console
     * @param OutputInterface $output
     * @return $this
     */
    public function setOutputInterface(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * Log actions of each data migration
     * @param AbstractDataMigration $dataMigration
     */
    private function logActions(AbstractDataMigration $dataMigration)
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
     * Truncate relevant tables before running
     */
    private function emptyTables()
    {
        foreach ($this->dataMigrationTypeProvider->getTypes() as $migrationType) {
            /** @var AbstractDataMigration $migrationType */
            $tableName = $this->resourceConnection->getTableName($migrationType->getTableName());
            $this->resourceConnection->getConnection()->delete($tableName);

            $this->resourceConnection->getConnection()
                ->query(sprintf('ALTER TABLE %s AUTO_INCREMENT = 1', $tableName));
        }
    }

    /**
     * Generate a random string and save in config
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function generateAndSaveCode()
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
}
