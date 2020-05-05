<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class Order extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_ORDER_TABLE, 'email_order_id');
    }

    /**
     * Reset the email order for re-import.
     *
     * @param string|null $from
     * @param string|null $to
     *
     * @return int
     *
     */
    public function resetOrders($from = null, $to = null)
    {
        $conn = $this->getConnection();
        if ($from && $to) {
            $where = [
                'created_at >= ?' => $from . ' 00:00:00',
                'created_at <= ?' => $to . ' 23:59:59',
                'email_imported = ?' => 1
            ];
        } else {
            $where = ['email_imported = ?' => 1];
        }
        $num = $conn->update(
            $this->getTable(Schema::EMAIL_ORDER_TABLE),
            [
                'email_imported' => 0,
                'modified' => new \Zend_Db_Expr('null'),
            ],
            $where
        );

        return $num;
    }

    /**
     * Mark the connector orders to be imported.
     *
     * @param array $ids
     *
     * @return null
     */
    public function setImported($ids)
    {
        if (empty($ids)) {
            return;
        }
        $connection = $this->getConnection();
        $tableName = $this->getTable(Schema::EMAIL_ORDER_TABLE);
        $connection->update(
            $tableName,
            [
                'modified' => new \Zend_Db_Expr('null'),
                'email_imported' => 1,
                'updated_at' => gmdate('Y-m-d H:i:s')
            ],
            ["order_id IN (?)" => $ids]
        );
    }
}
