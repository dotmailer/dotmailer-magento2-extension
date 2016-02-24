<?php

namespace Dotdigitalgroup\Email\Model\Resource;

class Importer extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('email_importer', 'id');
    }

    /**
     * Reset importer items
     *
     * @param $ids
     * @return int|string
     */
    public function massResend($ids)
    {
        try {
            $conn = $this->getConnection();
            $num = $conn->update($this->getTable('email_importer'),
                array('import_status' => 0),
                array('id IN(?)' => $ids)
            );
            return $num;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

}