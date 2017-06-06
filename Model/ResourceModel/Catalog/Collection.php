<?php

namespace Dotdigitalgroup\Email\Model\ResourceModel\Catalog;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'id';

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    public $productCollection;

    /**
     * Initialize resource collection.
     */
    public function _construct()
    {
        $this->_init(
            'Dotdigitalgroup\Email\Model\Catalog',
            'Dotdigitalgroup\Email\Model\ResourceModel\Catalog'
        );
    }

    /**
     * Collection constructor.
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollection,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->helper = $helper;
        $this->productCollection        = $productCollection;
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
     * Get product collection to export.
     *
     * @param      $store
     * @param      $limit
     * @param bool $modified
     *
     * @return mixed
     */
    public function getProductsToExportByStore($store, $limit, $modified = false)
    {

        $connectorCollection = $this;

        //for modified catalog
        if ($modified) {
            $connectorCollection->addFieldToFilter(
                'modified',
                ['eq' => '1']
            );
        } else {
            $connectorCollection->addFieldToFilter(
                'imported',
                ['null' => 'true']
            );
        }
        //set limit for collection
        $connectorCollection->setPageSize($limit);
        //check number of products
        if ($connectorCollection->getSize()) {
            $productIds = $connectorCollection->getColumnValues(
                'product_id'
            );
            $productCollection = $this->productCollection->create()
                ->addAttributeToSelect('*')
                ->addStoreFilter($store)
                ->addAttributeToFilter(
                    'entity_id',
                    ['in' => $productIds]
                );

            //visibility filter
            if ($visibility = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY
            )
            ) {
                $visibility = explode(',', $visibility);
                $productCollection->addAttributeToFilter(
                    'visibility',
                    ['in' => $visibility]
                );
            }
            //type filter
            if ($type = $this->helper->getWebsiteConfig(
                \Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE
            )
            ) {
                $type = explode(',', $type);
                $productCollection->addAttributeToFilter(
                    'type_id',
                    ['in' => $type]
                );
            }

            $productCollection->addWebsiteNamesToResult()
                ->addCategoryIds()
                ->addOptionsToResult();

            return $productCollection;
        }

        return false;
    }

    /**
     * Load product. return item otherwise create item.
     *
     * @param int $productId
     *
     * @return mixed
     */
    public function loadProductById($productId)
    {
        $collection = $this->addFieldToFilter('product_id', $productId)
            ->setPageSize(1);

        if ($collection->getSize()) {
            return $collection->getFirstItem();
        }

        return false;
    }
}
