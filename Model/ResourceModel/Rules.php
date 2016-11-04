<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

class Rules extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     */
    public function _construct()
    {
        $this->_init('email_rules', 'id');
    }

    /**
     * Mass delete
     *
     * @param $data
     * @return int|string
     */
    public function massDelete($data)
    {
        try {
            $ids = '"' . implode('","', $data) . '"';
            $conn = $this->getConnection();
            $num = $conn->delete(
                $this->getMainTable(),
                "id IN ($ids)"
            );

            return $num;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
