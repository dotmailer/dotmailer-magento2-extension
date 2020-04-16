<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Framework\DB\Select;

class InsertEmailOrderTable extends AbstractDataMigration implements InsertTypeInterface
{
    /**
     * @var string
     */
    protected $tableName = Schema::EMAIL_ORDER_TABLE;

    /**
     * @var string
     */
    protected $resourceName = 'sales';

    /**
     * @inheritdoc
     */
    protected function getSelectStatement()
    {
        return $this->resourceConnection
            ->getConnection($this->resourceName)
            ->select()
            ->from([
                $this->resourceConnection->getTableName('sales_order', $this->resourceName),
            ], [
                'order_id' => 'entity_id',
                'quote_id',
                'store_id',
                'created_at',
                'updated_at',
                'order_status' => 'status'
            ])
            ->order('order_id')
        ;
    }

    /**
     * @inheritdoc
     */
    public function getInsertArray()
    {
        return [
            'order_id',
            'quote_id',
            'store_id',
            'created_at',
            'updated_at',
            'order_status',
        ];
    }

    /**
     * For email_order, we must retrieve records first using the 'sales' connection.
     * Fetched records are then inserted into the target db/table as an array.
     * This alternate approach is required to support split databases.
     *
     * @param Select $selectStatement
     * @return int
     */
    protected function insertData(Select $selectStatement)
    {
        $fetched = $this->resourceConnection->getConnection($this->resourceName)
            ->fetchAll($selectStatement);

        if (empty($fetched)) {
            return 0;
        }

        return $this->resourceConnection
            ->getConnection()
            ->insertArray(
                $this->resourceConnection->getTableName($this->tableName),
                $this->getInsertArray(),
                $fetched
            );
    }
}
