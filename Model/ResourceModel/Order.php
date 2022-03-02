<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class Order extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     *
     * @return void
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
     */
    public function resetOrders($from = null, $to = null)
    {
        $conn = $this->getConnection();
        if ($from && $to) {
            $where = [
                'created_at >= ?' => $from . ' 00:00:00',
                'created_at <= ?' => $to . ' 23:59:59',
                'processed = ?' => 1
            ];
        } else {
            $where = ['processed = ?' => 1];
        }
        $num = $conn->update(
            $this->getTable(Schema::EMAIL_ORDER_TABLE),
            [
                'processed' => 0,
            ],
            $where
        );

        return $num;
    }

    /**
     * Update orders.
     *
     * @param array $ids
     * @return void
     */
    public function setProcessed(array $ids)
    {
        if (empty($ids)) {
            return;
        }
        $connection = $this->getConnection();
        $tableName = $this->getTable(Schema::EMAIL_ORDER_TABLE);
        $connection->update(
            $tableName,
            [
                'processed' => 1,
            ],
            ["order_id IN (?)" => $ids]
        );
    }

    /**
     * Update orders.
     *
     * @param array $ids
     * @return void
     */
    public function setUnProcessed(array $ids)
    {
        if (empty($ids)) {
            return;
        }
        $connection = $this->getConnection();
        $tableName = $this->getTable(Schema::EMAIL_ORDER_TABLE);
        $connection->update(
            $tableName,
            [
                'processed' => 0,
            ],
            ["order_id IN (?)" => $ids]
        );
    }

    /**
     * Set imported date.
     *
     * @param array $ids
     *
     * @return void
     */
    public function setImportedDateByIds(array $ids)
    {
        try {
            $coreResource = $this->getConnection();
            $tableName = $this->getTable(Schema::EMAIL_ORDER_TABLE);

            $coreResource->update(
                $tableName,
                [
                    'last_imported_at' => gmdate('Y-m-d H:i:s'),
                ],
                ["order_id IN (?)" => $ids]
            );
        } catch (\Exception $e) {
            $this->_logger->debug((string)$e, []);
        }
    }
}
