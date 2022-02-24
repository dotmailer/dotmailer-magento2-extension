<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Framework\DB\Select;

class UpdateEmailContactTableGuestSales extends AbstractBulkUpdater implements BulkUpdateTypeInterface
{
    /**
     * @var string
     */
    protected $resourceName = 'sales';

    /**
     * @var string
     */
    protected $tableName = Schema::EMAIL_CONTACT_TABLE;

    /**
     * Get this type's select statement
     *
     * @return Select
     */
    protected function getSelectStatement()
    {
        return $this->resourceConnection
            ->getConnection()
            ->select()
            ->from([
                'sales_order'=> $this->resourceConnection->getTableName('sales_order', $this->resourceName)
            ], [
                'is_guest' => new \Zend_Db_Expr('1'),
                'customer_email'
            ])
            ->distinct(true)
            ->joinInner(
                ['store' => $this->resourceConnection->getTableName('store')],
                'sales_order.store_id = store.store_id',
                ['website_id' => 'store.website_id']
            )
            ->where(
                '(sales_order.customer_email, store.website_id) IN (?)',
                $this->resourceConnection
                    ->getConnection()
                    ->select()
                    ->from(
                        $this->resourceConnection->getTableName(Schema::EMAIL_CONTACT_TABLE),
                        ['email', 'website_id']
                    )
                    ->where('customer_id != ?', 0)
            )
            ->where(
                $this->resourceConnection
                    ->getConnection()
                    ->prepareSqlCondition('sales_order.customer_id', [
                        'null' => true
                    ])
            );
    }

    /**
     * Get the bindings for this update
     *
     * @param bool $isGuest
     *
     * @return array
     */
    public function getUpdateBindings($isGuest)
    {
        return [
            'is_guest' => $isGuest,
        ];
    }

    /**
     * Get the where clause for this update
     *
     * @param array $row
     *
     * @return array
     */
    public function getUpdateWhereClause($row)
    {
        return [
            'email = ?' => $row['customer_email'],
            'website_id = ?' => $row['website_id']
        ];
    }

    /**
     * Get the bind key
     *
     * @return string
     */
    public function getBindKey(): string
    {
        return 'is_guest';
    }
}
