<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class InsertEmailContactTableCustomers extends AbstractDataMigration implements InsertTypeInterface
{
    /**
     * @var string
     */
    protected $tableName = Schema::EMAIL_CONTACT_TABLE;

    /**
     * @inheritdoc
     */
    protected function getSelectStatement()
    {
        return $this->resourceConnection
            ->getConnection()
            ->select()
            ->from([
                'customer' => $this->resourceConnection->getTableName('customer_entity'),
            ], [
                'customer_id' => 'entity_id',
                'email',
                'website_id',
                'store_id'
            ])
            ->order('customer_id')
        ;
    }

    /**
     * @inheritdoc
     */
    public function getInsertArray()
    {
        return [
            'customer_id',
            'email',
            'website_id',
            'store_id',
        ];
    }
}
