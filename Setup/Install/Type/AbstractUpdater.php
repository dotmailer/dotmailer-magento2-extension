<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Magento\Framework\DB\Select;

abstract class AbstractUpdater extends AbstractDataMigration
{
    /**
     * Run this type
     *
     * @return static
     */
    public function execute(): self
    {
        $this->update($this->getSelectStatement());
        return $this;
    }

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
}
