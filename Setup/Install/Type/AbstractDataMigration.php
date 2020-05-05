<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Magento\Framework\DB\Select;
use Magento\Framework\App\ResourceConnection;

abstract class AbstractDataMigration
{
    /**
     * The size queries will be batched in
     */
    const BATCH_SIZE = 500;

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
     * onDuplicate value
     * @var bool
     */
    protected $onDuplicate = false;

    /**
     * Flag whether batched queries should be offset
     * @var bool
     */
    protected $useOffset = true;

    /**
     * Rows affected by the change
     * @var int
     */
    protected $rowsAffected = 0;

    /**
     * AbstractType constructor
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Run this type
     * @return self
     * @throws \ErrorException
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute()
    {
        if ($this instanceof InsertTypeInterface) {
            $this->batchInsert($this->getSelectStatement());
        } elseif ($this instanceof UpdateTypeInterface) {
            $this->update($this->getSelectStatement());
        }

        return $this;
    }

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
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Get this type's select statement
     * @return Select
     */
    abstract protected function getSelectStatement();

    /**
     * Run an update statement
     *
     * @param Select $selectStatement
     * @return void
     */
    private function update(Select $selectStatement)
    {
        $this->rowsAffected += $this->resourceConnection
            ->getConnection()
            ->update(
                $this->resourceConnection->getTableName($this->tableName),
                $this->getUpdateBindings(),
                $this->getUpdateWhereClause($selectStatement)
            );
    }

    /**
     * @param Select $selectStatement
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function batchInsert(Select $selectStatement)
    {
        $iterations = $rowCount = 0;

        do {
            // select offset for query
            $selectStatement->limit(self::BATCH_SIZE, $this->useOffset ? $this->rowsAffected : 0);

            $rowCount = $this->insertData($selectStatement);

            // increase the batch offset
            $this->rowsAffected += $rowCount;

            // if the first iteration returned < the batch size, we can break here to avoid an additional queries
            if ($iterations++ === 0 && $rowCount < self::BATCH_SIZE) {
                break;
            }

        } while ($rowCount > 0);
    }

    /**
     * By default, records are directly inserted via the select statement.
     *
     * @param Select $selectStatement
     * @return int
     * @throws \Zend_Db_Statement_Exception
     */
    protected function insertData(Select $selectStatement)
    {
        $query = $selectStatement->insertFromSelect(
            $this->resourceConnection->getTableName($this->tableName),
            $this->getInsertArray(),
            $this->onDuplicate
        );
        return $this->resourceConnection
            ->getConnection()
            ->query($query)
            ->rowCount();
    }
}
