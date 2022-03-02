<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Framework\DB\Select;

class InsertEmailContactTableCustomerSales extends AbstractBatchInserter implements InsertTypeInterface
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
     * Do not use offset for this migration.
     *
     * @var bool
     */
    protected $useOffset = false;

    /**
     * Get this type's select statement
     *
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
                'NOT EXISTS (?)',
                $this->resourceConnection
                    ->getConnection()
                    ->select()
                    ->from(
                        $this->resourceConnection->getTableName(Schema::EMAIL_CONTACT_TABLE)
                    )
                    ->where('email = sales_order.customer_email')
                    ->where('website_id = store.website_id')
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
     * Get the insert array
     *
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
     * Insert data
     *
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
     * This type only runs if Account Sharing is Global.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isAccountSharingGlobal();
    }
}
