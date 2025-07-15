<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkCatalogRecordCollectionBuilderFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroupCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Expr;

class Exporter
{

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var CustomerGroupCollection
     */
    private $customerGroup;

    /**
     * @var SdkCatalogRecordCollectionBuilderFactory
     */
    private $sdkCatalogRecordCollectionBuilderFactory;

    /**
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CustomerGroupCollection $customerGroup
     * @param SdkCatalogRecordCollectionBuilderFactory $sdkCatalogRecordCollectionBuilderFactory
     */
    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $productCollectionFactory,
        CustomerGroupCollection $customerGroup,
        SdkCatalogRecordCollectionBuilderFactory $sdkCatalogRecordCollectionBuilderFactory
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->customerGroup = $customerGroup;
        $this->sdkCatalogRecordCollectionBuilderFactory = $sdkCatalogRecordCollectionBuilderFactory;
    }

    /**
     * Export catalog
     *
     * @param int|null $storeId
     * @param array $productsToProcess
     * @param int|null $customerGroupId
     *
     * @return array|array[]
     * @throws NoSuchEntityException
     */
    public function exportCatalog(?int $storeId, $productsToProcess, ?int $customerGroupId = null): array
    {
        try {
            $products = $this->getProductsToExport($storeId, $productsToProcess);
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
            return [];
        }

        if ($products->getSize() === 0) {
            return [];
        }

        return $this->sdkCatalogRecordCollectionBuilderFactory->create([
                'storeId' => $storeId,
                'customerGroupId' => $customerGroupId
            ])->setBuildableData($products)
            ->build()
            ->all();
    }

    /**
     * Get product collection to export.
     *
     * @param int|null $storeId
     * @param array $productIds
     *
     * @return ProductCollection
     * @throws NoSuchEntityException
     */
    private function getProductsToExport(?int $storeId, array $productIds): ProductCollection
    {
        $collection = $this->filterProductsByStoreTypeAndVisibility(
            $storeId,
            $productIds,
            $this->getAllowedProductTypes($storeId),
            $this->getAllowedProductVisibilities($storeId)
        );

        return $this->joinIndexedPrices($collection, $storeId);
    }

    /**
     * Get allowed product types.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    private function getAllowedProductTypes(?int $storeId): array
    {
        $types = explode(
            ',',
            $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );
        return array_filter($types);
    }

    /**
     * Get allowed product visibilities.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    private function getAllowedProductVisibilities(?int $storeId): array
    {
        $visibilities = explode(
            ',',
            $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY,
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
        );
        return array_filter($visibilities);
    }

    /**
     * Get product collection to export.
     *
     * @param int|null $storeId
     * @param array $productIds
     * @param array $types
     * @param array $visibilities
     *
     * @return ProductCollection
     */
    private function filterProductsByStoreTypeAndVisibility(
        ?int $storeId,
        array $productIds,
        array $types,
        array $visibilities
    ): ProductCollection {
        $productCollection = $this->productCollectionFactory->create()
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
     * Utility method to retrieve catalog rule pricing.
     *
     * @param ProductCollection $productCollection
     * @param int|null $storeId
     *
     * @return ProductCollection
     * @throws NoSuchEntityException
     */
    private function joinIndexedPrices(ProductCollection $productCollection, ?int $storeId): ProductCollection
    {
        if (!$this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_SYNC_CATALOG_INDEX_PRICES_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        )) {
            return $productCollection;
        }

        $customerGroups = $this->customerGroup->toOptionArray();
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        foreach ($customerGroups as $customerGroup) {
            $groupId = $customerGroup['value'];
            $groupLabel = $customerGroup['label'];
            $alias = 'price_index_' . $groupId;
            $productCollection->getSelect()->joinLeft(
                [$alias => $productCollection->getTable('catalog_product_index_price')],
                "e.entity_id = " . $alias . ".entity_id AND " .
                $alias . ".customer_group_id = " . $groupId . " AND " .
                $alias . ".website_id = " . $websiteId,
                [
                    "index_pricing_price_" . $groupId => 'price',
                    "index_pricing_final_price_" . $groupId => 'final_price',
                    "index_pricing_min_price_" . $groupId => 'min_price',
                    "index_pricing_max_price_" . $groupId => 'max_price',
                    "index_pricing_tier_price_" . $groupId => 'tier_price',
                    "index_pricing_group_name_" . $groupId => new Zend_Db_Expr("'$groupLabel'")
                ]
            );
        }

        return $productCollection;
    }
}
