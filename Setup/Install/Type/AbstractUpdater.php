<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Magento\Framework\DB\Select;

/**
 * @deprecated No longer used.
 * @see AbstractBulkUpdater
 */
abstract class AbstractUpdater extends AbstractDataMigration
{
    /**
     * Run this type
     *
     * @return static
     */
    public function execute(): self
    {
        $this->batchUpdate($this->getSelectStatement());
        return $this;
    }

    /**
     * Run an update statement
     *
     * @param Select $selectStatement
     *
     * @return void
     */
    private function batchUpdate(Select $selectStatement)
    {
        $totalRowsSelected = 0;

        do {
            $selectStatement->limit(self::BATCH_SIZE, $this->useOffset ? $totalRowsSelected : 0);
            $rowsSelected = $this->countSelected($selectStatement);
            $totalRowsSelected += $rowsSelected;

            $rowsUpdated = $this->updateData($selectStatement);
            $this->rowsAffected += $rowsUpdated;
        } while ($rowsSelected === self::BATCH_SIZE);
    }

    /**
     * Get the bindings for this update
     *
     * @return array
     */
    abstract protected function getUpdateBindings();

    /**
     * Get the where clause for this update
     *
     * @param Select $selectStatement
     *
     * @return array
     */
    abstract protected function getUpdateWhereClause(Select $selectStatement);

    /**
     * Update data.
     *
     * @param Select $selectStatement
     *
     * @return int
     */
    protected function updateData(Select $selectStatement): int
    {
        return $this->resourceConnection
            ->getConnection()
            ->update(
                $this->resourceConnection->getTableName($this->tableName),
                $this->getUpdateBindings(),
                $this->getUpdateWhereClause($selectStatement)
            );
    }

    /**
     * Count selected rows.
     *
     * @param Select $selectStatement
     *
     * @return int
     */
    protected function countSelected(Select $selectStatement): int
    {
        $result = $this->resourceConnection
            ->getConnection()
            ->fetchAll($selectStatement);

        return count($result);
    }
}
