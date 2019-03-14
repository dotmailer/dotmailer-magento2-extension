<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Magento\Framework\DB\Select;
use Magento\Framework\Setup\ModuleDataSetupInterface;

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
     * @var ModuleDataSetupInterface
     */
    protected $installer;

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
     * @param ModuleDataSetupInterface $installer
     */
    public function __construct(ModuleDataSetupInterface $installer)
    {
        $this->installer = $installer;
    }

    /**
     * Run this type
     * @return self
     * @throws \ErrorException
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute()
    {
        $this->batch($this->getSelectStatement());
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
     * Actions to be performed after queries have run
     * Override as necessary
     * @return void
     */
    protected function afterInstall()
    {
    }

    /**
     * @param Select $selectStatement
     * @return void
     * @throws \ErrorException
     * @throws \Zend_Db_Statement_Exception
     */
    private function batch(Select $selectStatement)
    {
        $iterations = $rowCount = 0;

        do {
            // select offset for query
            $selectStatement->limit(self::BATCH_SIZE, $this->useOffset ? $this->rowsAffected : 0);

            // get and perform query, returning the rows affected
            switch (true) {
                // inserts
                case $this instanceof InsertTypeInterface :
                    $query = $selectStatement->insertFromSelect(
                        $this->installer->getTable($this->tableName),
                        $this->getInsertArray(),
                        $this->onDuplicate
                    );
                    $rowCount = $this->installer
                        ->getConnection()
                        ->query($query)
                        ->rowCount();
                    break;

                // updates
                case $this instanceof UpdateTypeInterface :
                    $rowCount = $this->installer
                        ->getConnection()
                        ->update(
                            $this->installer->getTable($this->tableName),
                            $this->getUpdateBindings(),
                            $this->getUpdateWhereClause($selectStatement)
                        );
                    break;

                default :
                    throw new \ErrorException('A query type must be implemented');
            }

            // increase the batch offset
            $this->rowsAffected += $rowCount;

            // if the first iteration returned < the batch size, we can break here to avoid an additional queries
            if ($iterations++ === 0 && $rowCount < self::BATCH_SIZE) {
                break;
            }

        } while ($rowCount > 0);

        // run any post-install operations
        $this->afterInstall();
    }
}