<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class InsertEmailWishlistTable extends AbstractDataMigration implements InsertTypeInterface
{
    /**
     * @var string
     */
    protected $tableName = Schema::EMAIL_WISHLIST_TABLE;

    /**
     * @inheritdoc
     */
    protected function getSelectStatement()
    {
        return $this->resourceConnection
            ->getConnection()
            ->select()
            ->distinct()
            ->from([
                'wishlist' => $this->resourceConnection->getTableName('wishlist'),
            ], [
                'wishlist_id',
                'customer_id',
                'created_at' => 'updated_at',
            ])
            ->joinInner(
                ['wi' => $this->resourceConnection->getTableName('wishlist_item')],
                'wishlist.wishlist_id = wi.wishlist_id',
                ['store_id', 'item_count' => 'count(wi.wishlist_id)']
            )
            ->group(['wi.wishlist_id', 'wi.store_id'])
            ->order('wi.wishlist_id');
    }

    /**
     * @inheritdoc
     */
    public function getInsertArray()
    {
        return [
            'wishlist_id',
            'customer_id',
            'created_at',
            'store_id',
            'item_count',
        ];
    }
}
