<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Order extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_order', 'email_order_id');
    }

    /**
     * Reset the email order for re-import.
     *
     * @param null $from
     * @param null $to
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetOrders($from = null, $to = null)
    {
        $conn = $this->getConnection();
        if ($from && $to) {
            $where = [
                'created_at >= ?' => $from . ' 00:00:00',
                'created_at <= ?' => $to . ' 23:59:59',
                'email_imported is ?' => new \Zend_Db_Expr('not null')
            ];
        } else {
            $where = $conn->quoteInto(
                'email_imported is ?',
                new \Zend_Db_Expr('not null')
            );
        }
        try {
            $num = $conn->update(
                $conn->getTableName('email_order'),
                [
                    'email_imported' => new \Zend_Db_Expr('null'),
                    'modified' => new \Zend_Db_Expr('null'),
                ],
                $where
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }


    /**
     * Mark the connector orders to be imported.
     *
     * @param $ids
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setImported($ids)
    {
        if (empty($ids)) {
            return ;
        }
        try {
            $connection = $this->getConnection();
            $tableName = $connection->getTableName('email_order');
            $ids = implode(', ', $ids);
            $connection->update(
                $tableName,
                [
                    'modified' => new \Zend_Db_Expr('null'),
                    'email_imported' => '1',
                    'updated_at' => gmdate('Y-m-d H:i:s')
                ],
                "order_id IN ($ids)"
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
    }
}
