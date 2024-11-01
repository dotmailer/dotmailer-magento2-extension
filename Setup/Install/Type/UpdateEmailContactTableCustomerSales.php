<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

/**
 * For Global Account Sharing only.
 *
 * Set customer_id on any contact rows where the email matches other customer orders in sales_order.
 * For example we may have:
 * - a customer row created for chaz@emailsim.io (website 1)
 * - a subscriber created for chaz@emailsim.io (website 2)
 * - order history for chaz@emailsim.io in website 2
 * In GAS, there should be a row per website id, with the same customer_id,
 * even if the website 2 order was a guest order.
 */
class UpdateEmailContactTableCustomerSales extends AbstractBulkUpdater implements BulkUpdateTypeInterface
{
    /**
     * @var string
     */
    private $resourceName = 'sales';

    /**
     * @var string
     */
    protected $tableName = Schema::EMAIL_CONTACT_TABLE;

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function getUpdateBindings($bind)
    {
        return [
            'customer_id' => $bind['customer_id'],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getUpdateWhereClause($row)
    {
        return [
            'email in (?)' => $row['customer_email'],
        ];
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->config->isAccountSharingGlobal();
    }

    /**
     * @inheritDoc
     */
    public function getBindKey(): string
    {
        return 'customer_id';
    }
}
