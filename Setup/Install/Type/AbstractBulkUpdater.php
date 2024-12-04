<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Magento\Framework\DB\Select;

abstract class AbstractBulkUpdater extends AbstractDataMigration
{
    /**
     * Run this type
     *
     * @return static
     */
    public function execute(): self
    {
        $this->bulkUpdate($this->getSelectStatement());
        return $this;
    }

    /**
     * Run a bulk update statement
     *
     * @param Select $selectStatement
     *
     * @return void
     */
    protected function bulkUpdate(Select $selectStatement)
    {
        $totalRowsSelected = 0;

        do {
            $selectStatement->limit(self::BATCH_SIZE, $this->useOffset ? $totalRowsSelected : 0);
            $records = $this->fetchRecords($selectStatement);
            $rowsSelected = count($records);
            $totalRowsSelected += $rowsSelected;

            foreach ($records as $record) {
                $this->rowsAffected += $this->resourceConnection
                    ->getConnection()
                    ->update(
                        $this->resourceConnection->getTableName($this->tableName),
                        $this->getUpdateBindings($record),
                        $this->getUpdateWhereClause($record)
                    );
            }
        } while ($rowsSelected === self::BATCH_SIZE);
    }

    /**
     * Fetch records for this update
     *
     * @param Select $selectStatement
     *
     * @return array
     */
    protected function fetchRecords(Select $selectStatement)
    {
        return $this->resourceConnection
            ->getConnection()
            ->fetchAll($selectStatement);
    }

    /**
     * Get the bindings for this update
     *
     * The bindings are the column-value pairs we are updating in the query.
     *
     * @param mixed $bind
     *
     * @return mixed
     */
    abstract protected function getUpdateBindings($bind);

    /**
     * Get the key for the update clause
     *
     * @return mixed
     *
     * @deprecated getUpdateBindings() needs to receive an array for flexibility.
     * @see getUpdateBindings
     */
    abstract protected function getBindKey();

    /**
     * Get the where clause for this update
     *
     * @param array $row
     *
     * @return array
     */
    abstract protected function getUpdateWhereClause(array $row);
}
