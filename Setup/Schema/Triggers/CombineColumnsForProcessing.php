<?php

namespace Dotdigitalgroup\Email\Setup\Schema\Triggers;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Declaration\Schema\Db\DDLTriggerInterface;
use Magento\Framework\Setup\Declaration\Schema\Dto\Column;
use Magento\Framework\Setup\Declaration\Schema\ElementHistory;

/**
 * Combines 'imported' and 'modified' column values when creating the 'processed' column
 */
class CombineColumnsForProcessing implements DDLTriggerInterface
{
    /**
     * Trigger name - used to check if this trigger is applicable.
     * For reusable triggers we should use a regex pattern see MigrateDataFrom.
     */
    const TRIGGER_NAME = 'combineColumnsForProcessing';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Constructor.
     *
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc
     */
    public function isApplicable(string $statement) : bool
    {
        return $statement === self::TRIGGER_NAME;
    }

    /**
     * @inheritdoc
     */
    public function getCallback(ElementHistory $columnHistory) : callable
    {
        /** @var Column $column */
        $column = $columnHistory->getNew();

        return function () use ($column) {
            $tableName = $column->getTable()->getName();
            $adapter = $this->resourceConnection->getConnection(
                $column->getTable()->getResource()
            );

            if ($adapter->tableColumnExists($tableName, 'imported') &&
                $adapter->tableColumnExists($tableName, 'modified')
            ) {
                $adapter
                    ->update(
                        $tableName,
                        [
                            $column->getName() => 1
                        ],
                        [
                            'imported' => 1,
                            'modified IS NULL OR modified = 0'
                        ]
                    );
            }
        };
    }
}
