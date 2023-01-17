<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Catalog;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    public $productCollection;

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
     * Collection constructor.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->productCollection = $productCollection;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
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
     * Get product collection to export.
     *
     * @param string|int|null $storeId
     * @param array $productIds
     * @param array $types
     * @param array $visibilities
     *
     * @return ProductCollection
     */
    public function filterProductsByStoreTypeAndVisibility(
        $storeId,
        array $productIds,
        array $types,
        array $visibilities
    ) {
        $productCollection = $this->productCollection->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter(
                'entity_id',
                ['in' => $productIds]
            )->addUrlRewrite();

        if (!empty($storeId)) {
            $productCollection->addStoreFilter($storeId);
        }

        if ($visibilities) {
            $productCollection->addAttributeToFilter(
                'visibility',
                ['in' => $visibilities]
            );
        }

        if ($types) {
            $productCollection->addAttributeToFilter(
                'type_id',
                ['in' => $types]
            );
        }

        $productCollection->addWebsiteNamesToResult()
            ->addCategoryIds()
            ->addOptionsToResult();

        $productCollection->clear();

        return $productCollection;
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
