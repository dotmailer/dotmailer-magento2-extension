<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Framework\DB\Select;

class InsertEmailContactTableCustomerSales extends AbstractDataMigration implements InsertTypeInterface
{
    /**
     * @var string
     */
    protected $tableName = Schema::EMAIL_CONTACT_TABLE;

    /**
     * @var string
     */
    protected $resourceName = 'sales';

    /**
     * @return \Magento\Framework\DB\Select|void
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
                'customer_email',
                'MIN(`sales_order`.`store_id`)'
            ])
            ->distinct(true)
            ->joinInner(
                ['store' => $this->resourceConnection->getTableName('store')],
                'sales_order.store_id = store.store_id',
                ['website_id' => 'store.website_id']
            )
            ->where(
                '(sales_order.customer_email, store.website_id) NOT IN (?)',
                $this->resourceConnection
                    ->getConnection()
                    ->select()
                    ->from(
                        $this->resourceConnection->getTableName(Schema::EMAIL_CONTACT_TABLE),
                        ['email', 'website_id']
                    )
            )->where(
                $this->resourceConnection
                    ->getConnection()
                    ->prepareSqlCondition('sales_order.customer_id', [
                        'notnull' => true
                    ])
            )
            ->group(['customer_id', 'customer_email', 'website_id']);
    }

    /**
     * @return array|void
     */
    public function getInsertArray()
    {
        return [
            'customer_id',
            'email',
            'store_id',
            'website_id'
        ];
    }

    /**
     * @param Select $selectStatement
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
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

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isAccountSharingGlobal();
    }
}
