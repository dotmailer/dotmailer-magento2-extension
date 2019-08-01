<?php

namespace Dotdigitalgroup\Email\Setup\Install\Type;

use Dotdigitalgroup\Email\Setup\Schema;
use Magento\Framework\DB\Select;

class UpdateContactsWithSubscriberCustomers extends AbstractDataMigration implements UpdateTypeInterface
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
                'customer_id'
            )
            ->where('subscriber_status = ?', 1)
            ->where('customer_id > ?', 0)
            ->order('customer_id')
        ;
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
    public function getUpdateWhereClause()
    {
        // get customer IDs
        $customerIds = $this->resourceConnection
            ->getConnection()
            ->fetchCol($this->getSelectStatement());

        return [
            'customer_id in (?)' => $customerIds,
        ];
    }
}