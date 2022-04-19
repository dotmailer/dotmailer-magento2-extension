<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class InsertEmailContactTableSubscribers extends AbstractBatchInserter implements InsertTypeInterface
{
    /**
     * @var string
     */
    protected $tableName = Schema::EMAIL_CONTACT_TABLE;

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
        return $this->resourceConnection
            ->getConnection()
            ->select()
            ->from([
                'subscriber' => $this->resourceConnection->getTableName('newsletter_subscriber'),
            ], [
                'email' => 'subscriber_email',
                'customer_id' => new \Zend_Db_Expr('0'),
                'is_subscriber' => new \Zend_Db_Expr('1'),
                'subscriber_status' => new \Zend_Db_Expr('1'),
                'store_id',
            ])
            ->joinInner(
                ['store' => $this->resourceConnection->getTableName('store')],
                'subscriber.store_id = store.store_id',
                ['website_id' => 'store.website_id']
            )->joinLeft(
                ['email_contact' => $this->resourceConnection->getTableName($this->tableName)],
                'subscriber.subscriber_email = email_contact.email and subscriber.store_id = email_contact.store_id',
                []
            )
            ->where('email is ?', new \Zend_Db_Expr('null'))
            ->where('subscriber.customer_id = ?', 0)
            ->where('subscriber.subscriber_status = ?', 1)
            ->order('subscriber.subscriber_id');
    }

    /**
     * @inheritdoc
     */
    public function getInsertArray()
    {
        return [
            'email',
            'customer_id',
            'is_subscriber',
            'subscriber_status',
            'store_id',
            'website_id',
        ];
    }
}
