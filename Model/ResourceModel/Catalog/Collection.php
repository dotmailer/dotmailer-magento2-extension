<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\ResourceModel\Catalog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize resource collection.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            \Dotdigitalgroup\Email\Model\Catalog::class,
            \Dotdigitalgroup\Email\Model\ResourceModel\Catalog::class
        );
    }

    /**
     * Get unprocessed products.
     *
     * @param int $limit
     *
     * @return array
     */
    public function getUnprocessedProducts($limit)
    {
        $connectorCollection = $this;
        $connectorCollection->addFieldToFilter('processed', '0');
        $connectorCollection->getSelect()->limit($limit);
        $connectorCollection->setOrder(
            'product_id',
            'asc'
        );

        //check number of products
        if ($connectorCollection->getSize()) {
            return $connectorCollection->getColumnValues('product_id');
        }

        return [];
    }

    /**
     * Get products without a 'processed' filter.
     *
     * @param int $limit
     *
     * @return array
     */
    public function getProducts($limit)
    {
        $connectorCollection = $this;
        $connectorCollection->getSelect()->limit($limit);
        $connectorCollection->setOrder(
            'product_id',
            'asc'
        );

        return $connectorCollection->getColumnValues('product_id');
    }

    /**
     * Utility method to return all the product ids in a collection.
     *
     * @return array
     */
    public function getAllProductIds()
    {
        $ids = [];
        foreach ($this->getItems() as $item) {
            $ids[] = $item->getProductId();
        }
        return $ids;
    }
}
