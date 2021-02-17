<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Framework\DB\Select;

class UpdateEmailContactTableCustomerSales extends AbstractDataMigration implements BulkUpdateTypeInterface
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
                'customer_id',
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
                    ->where('customer_id = ?', 0)
            )
            ->where(
                $this->resourceConnection
                    ->getConnection()
                    ->prepareSqlCondition('sales_order.customer_id', [
                        'notnull' => true
                    ])
            );
    }

    /**
     * @param $customerId
     * @return array
     */
    public function getUpdateBindings($customerId)
    {
        return [
            'customer_id' => $customerId,
        ];
    }

    /**
     * @param $contactEmail
     * @return array
     */
    public function getUpdateWhereClause($contactEmail)
    {
        return [
            'email in (?)' => $contactEmail,
        ];
    }

    /**
     * @return array
     */
    public function fetchRecords()
    {
        return $this->resourceConnection
            ->getConnection()
            ->fetchAll($this->getSelectStatement());
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isAccountSharingGlobal();
    }

    /**
     * @return string
     */
    public function getWhereKey(): string
    {
        return 'customer_email';
    }

    /**
     * @return string
     */
    public function getBindKey(): string
    {
        return 'customer_id';
    }
}
