<?php

namespace Dotdigitalgroup\Email\Model\Resource;

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
     * Reset the email order for reimport.
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetOrders()
    {
        $conn = $this->getConnection();
        try {
            $num = $conn->update($conn->getTableName('email_order'),
                [
                    'email_imported' => new \Zend_Db_Expr('null'),
                    'modified' => new \Zend_Db_Expr('null'),
                ],
                $conn->quoteInto('email_imported is ?',
                    new \Zend_Db_Expr('not null'))
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }
}
