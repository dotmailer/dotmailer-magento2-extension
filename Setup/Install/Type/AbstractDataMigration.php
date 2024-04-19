<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Console\Command\ImportDataCommand;
use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

abstract class AbstractDataMigration
{
    /**
     * The size queries will be batched in
     */
    public const BATCH_SIZE = 1000;

    /**
     * The table name this type writes to
     * @var string
     */
    protected $tableName;

    /**
     * The MigrationHelper should contain any dependencies required by the updates
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Rows affected by the change
     * @var int
     */
    protected $rowsAffected = 0;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $config
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig,
        Config $config
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
    }

    /**
     * Run the migration according to type.
     *
     * @return static
     */
    abstract public function execute();

    /**
     * Get this type's select statement
     *
     * @return Select
     */
    abstract protected function getSelectStatement();

    /**
     * Get the rows affected by this type
     *
     * @return int
     */
    public function getRowsAffected()
    {
        return $this->rowsAffected;
    }

    /**
     * Get the table name
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Is this migration type enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        global $argv;
        return (isset($argv) && is_array($argv) && in_array(ImportDataCommand::CLI_COMMAND, $argv));
    }
}
