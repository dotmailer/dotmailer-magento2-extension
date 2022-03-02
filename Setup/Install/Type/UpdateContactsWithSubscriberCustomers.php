<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Framework\DB\Select;

class UpdateContactsWithSubscriberCustomers extends AbstractUpdater implements UpdateTypeInterface
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
            ->from(
                $this->resourceConnection->getTableName('newsletter_subscriber'),
                'subscriber_email'
            )
            ->where('subscriber_status = ?', 1)
            ->order('subscriber_email');
    }

    /**
     * @inheritdoc
     */
    public function getUpdateBindings()
    {
        return [
            'is_subscriber' => new \Zend_Db_Expr('1'),
            'subscriber_status' => new \Zend_Db_Expr('1'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUpdateWhereClause(Select $selectStatement)
    {
        $emails = $this->resourceConnection
            ->getConnection()
            ->fetchCol($selectStatement);

        return [
            'email in (?)' => $emails,
        ];
    }
}
