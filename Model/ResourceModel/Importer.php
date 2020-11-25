<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;

class Importer extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(Schema::EMAIL_IMPORTER_TABLE, 'id');
    }

    /**
     * Reset importer items.
     *
     * @param array $ids
     *
     * @return int|string
     */
    public function massResend($ids)
    {
        try {
            $conn = $this->getConnection();
            $num = $conn->update(
                $this->getTable(Schema::EMAIL_IMPORTER_TABLE),
                ['import_status' => 0],
                ['id IN(?)' => $ids]
            );

            return $num;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Save item
     *
     * @param \Dotdigitalgroup\Email\Model\Importer $item
     *
     * @return $this
     */
    public function saveItem($item)
    {
        return $this->save($item);
    }
}
