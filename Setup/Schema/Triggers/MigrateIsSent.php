<?php

namespace Dotdigitalgroup\Email\Setup\Schema\Triggers;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Declaration\Schema\Db\DDLTriggerInterface;
use Magento\Framework\Setup\Declaration\Schema\Dto\Column;
use Magento\Framework\Setup\Declaration\Schema\ElementHistory;

/**
 * Migrates email_campaign is_sent = 1 to send_status = 2
 */
class MigrateIsSent implements DDLTriggerInterface
{
    /**
     * Trigger name - used to check if this trigger is applicable.
     * For reusable triggers we should use a regex pattern see MigrateDataFrom.
     */
    const TRIGGER_NAME = 'migrateIsSent';

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

            if ($adapter->tableColumnExists($tableName, 'is_sent')) {
                $adapter
                    ->update(
                        $tableName,
                        [
                            $column->getName() => new \Zend_Db_Expr(
                                \Dotdigitalgroup\Email\Model\Campaign::SENT
                            )
                        ],
                        [
                            'is_sent' => 1
                        ]
                    );
            }
        };
    }
}
