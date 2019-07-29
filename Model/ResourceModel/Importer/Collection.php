<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Importer;

class Collection extends
 \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize resource collection.
     *
     * @return null
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Importer::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Importer::class
        );
    }

    /**
     * Reset collection.
     *
     * @return null
     */
    public function reset()
    {
        $this->_reset();
    }

    /**
     * Get imports marked as importing.
     *
     * @param int $limit
     *
     * @return $this|boolean
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
     * @param string|array $importType
     * @param string $importMode
     * @param int $limit
     * @param array $websiteIds
     *
     * @return $this
     */
    public function getQueueByTypeAndMode($importType, $importMode, $limit, $websiteIds)
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
            );

        $this->addFieldToFilter('website_id', ['in' => $websiteIds]);

        $this->setPageSize($limit)
            ->setCurPage(1);

        return $this;
    }
}
