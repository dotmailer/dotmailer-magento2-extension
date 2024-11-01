<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class UpdateContactsWithSubscriberCustomers extends AbstractBulkUpdater implements BulkUpdateTypeInterface
{
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
                'subscriber' => $this->resourceConnection->getTableName('newsletter_subscriber')
            ], [
                'subscriber_email',
                'store.website_id'
            ])
            ->joinInner(
                ['store' => $this->resourceConnection->getTableName('store')],
                'subscriber.store_id = store.store_id',
                ['website_id' => 'store.website_id']
            )
            ->where('subscriber_status = ?', 1)
            ->order('subscriber_email');
    }

    /**
     * @inheritDoc
     */
    public function getUpdateBindings($bind)
    {
        return [
            'is_subscriber' => new \Zend_Db_Expr('1'),
            'subscriber_status' => new \Zend_Db_Expr('1'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getUpdateWhereClause($row)
    {
        return [
            'email = ?' => $row['subscriber_email'],
            'website_id = ?' => $row['website_id']
        ];
    }

    /**
     * @inheritDoc
     */
    public function getBindKey()
    {
        return '';
    }
}
