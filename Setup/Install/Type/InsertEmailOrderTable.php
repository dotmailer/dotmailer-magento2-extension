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
     * @inheritdoc
     */
    protected function getSelectStatement()
    {
        return $this->resourceConnection
            ->getConnection('sales')
            ->select()
            ->from([
                $this->resourceConnection->getTableName('sales_order', 'sales'),
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
}
