<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

abstract class AbstractBulkUpdater extends AbstractDataMigration
{
    /**
     * Run this type
     *
     * @return static
     */
    public function execute(): self
    {
        $this->bulkUpdate();
        return $this;
    }

    /**
     * Run a bulk update statement
     *
     * @return void
     */
    private function bulkUpdate()
    {
        foreach ($this->fetchRecords() as $record) {
            $this->rowsAffected += $this->resourceConnection
                ->getConnection()
                ->update(
                    $this->resourceConnection->getTableName($this->tableName),
                    $this->getUpdateBindings($record[$this->getBindKey()]),
                    $this->getUpdateWhereClause($record)
                );
        }
    }

    /**
     * Fetch records for this update
     *
     * @return array
     */
    private function fetchRecords()
    {
        return $this->resourceConnection
            ->getConnection()
            ->fetchAll($this->getSelectStatement());
    }

    /**
     * Get the bindings for this update
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
