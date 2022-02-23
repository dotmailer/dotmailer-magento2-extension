<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel;

use Dotdigitalgroup\Email\Setup\SchemaInterface as Schema;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;

class Importer extends AbstractDb
{
    /**
     * Initialize resource.
     *
     * @return void
     */
    public function _construct():void
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
    public function massReset(array $ids)
    {
        try {

            $conn = $this->getConnection();
            $tableName = $this->getTable(Schema::EMAIL_IMPORTER_TABLE);

            return $conn->update(
                $tableName,
                [
                    'import_status' => 0,
                    'import_id' => null,
                    'import_started' => null,
                    'import_finished' => null,
                    'updated_at' => gmdate('Y-m-d H:i:s'),
                ],
                ['id IN(?)' => $ids]
            );

        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Save item
     *
     * @param ImporterModel $item
     *
     * @return Importer $this
     * @throws AlreadyExistsException
     */
    public function saveItem(ImporterModel $item):Importer
    {
        return $this->save($item);
    }
}
