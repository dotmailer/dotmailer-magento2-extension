<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Catalog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_catalog', 'id');
    }

    /**
     * Reset for re-import.
     *
     * @return int
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resetCatalog()
    {
        $conn = $this->getConnection();
        try {
            $num = $conn->update(
                $conn->getTableName('email_catalog'),
                [
                    'imported' => new \Zend_Db_Expr('null'),
                    'modified' => new \Zend_Db_Expr('null'),
                ]
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }

        return $num;
    }
}
