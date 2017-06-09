<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Importer;

class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'id';

    /**
     * Initialize resource collection.
     */
    public function _construct()
    {
        $this->_init('Dotdigitalgroup\Email\Model\Importer', 'Dotdigitalgroup\Email\Model\ResourceModel\Importer');
    }

    /**
     * Reset collection.
     */
    public function reset()
    {
        $this->_reset();
    }

    /**
     * Get imports marked as importing.
     *
     * @param $limit
     * @return mixed
     */
    public function getItemsWithImportingStatus($limit)
    {
        $collection = $this->addFieldToFilter(
            'import_status',
            ['eq' => \Dotdigitalgroup\Email\Model\Importer::IMPORTING]
        )
            ->addFieldToFilter('import_id', ['neq' => ''])
            ->setPageSize($limit)
            ->setCurPage(1);

        if ($collection->getSize()) {
            return $collection;
        }

        return false;
    }

    /**
     * Get the imports by type and mode.
     *
     * @param $importType
     * @param $importMode
     * @param $limit
     * @return mixed
     */
    public function getQueueByTypeAndMode($importType, $importMode, $limit)
    {
        if (is_array($importType)) {
            $condition = [];
            foreach ($importType as $type) {
                if ($type == 'Catalog') {
                    $condition[] = ['like' => $type . '%'];
                } else {
                    $condition[] = ['eq' => $type];
                }
            }
            $this->addFieldToFilter('import_type', $condition);
        } else {
            $this->addFieldToFilter(
                'import_type',
                ['eq' => $importType]
            );
        }

        $this->addFieldToFilter('import_mode', ['eq' => $importMode])
            ->addFieldToFilter(
                'import_status',
                ['eq' => \Dotdigitalgroup\Email\Model\Importer::NOT_IMPORTED]
            )
            ->setPageSize($limit)
            ->setCurPage(1);

        return $this;
    }
}
