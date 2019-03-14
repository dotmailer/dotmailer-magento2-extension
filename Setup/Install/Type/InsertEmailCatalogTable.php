<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\Schema;

class InsertEmailCatalogTable extends AbstractDataMigration implements InsertTypeInterface
{
    /**
     * @var string
     */
    protected $tableName = Schema::EMAIL_CATALOG_TABLE;

    /**
     * Don't offset the query for this migration
     * @var bool
     */
    protected $useOffset = false;

    /**
     * @inheritdoc
     */
    protected function getSelectStatement()
    {
        return $this->installer
            ->getConnection()
            ->select()
            ->from([
                'catalog' => $this->installer->getTable('catalog_product_entity'),
            ], [
                'product_id' => 'catalog.entity_id',
                'created_at' => 'catalog.created_at',
            ])
            ->where(
                'catalog.entity_id NOT IN (?)',
                $this->installer
                    ->getConnection()
                    ->select()
                    ->from($this->installer->getTable(Schema::EMAIL_CATALOG_TABLE), ['product_id'])
            )
            ->order('catalog.entity_id')
        ;
    }

    /**
     * @inheritdoc
     */
    public function getInsertArray()
    {
        return [
            'product_id',
            'created_at',
        ];
    }

}