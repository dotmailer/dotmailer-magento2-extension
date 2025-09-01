<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Magento\Framework\DB\Select;

abstract class AbstractBatchInserter extends AbstractDataMigration
{
    /**
     * onDuplicate value
     * @var bool
     */
    private $onDuplicate = false;

    /**
     * Run this type
     *
     * @return static
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(): self
    {
        $this->batchInsert($this->getSelectStatement());
        return $this;
    }

    /**
     * Get the insert fields for this type
     *
     * @return array
     */
    abstract protected function getInsertArray();

    /**
     * Run a batch insert
     *
     * @param Select $selectStatement
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function batchInsert(Select $selectStatement)
    {
        do {
            // select offset for query
            $selectStatement->limit($this->batchSize, $this->useOffset ? $this->rowsAffected : 0);

            $rowCount = $this->insertData($selectStatement);

            // increase the batch offset
            $this->rowsAffected += $rowCount;

        } while ($rowCount === $this->batchSize);
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
