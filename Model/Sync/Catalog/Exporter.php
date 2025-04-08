<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ProductFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\SchemaValidationException;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroupCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Exporter
{
    /**
     * @var ProductFactory
     */
    private $connectorProductFactory;

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
     * @param ProductFactory $connectorProductFactory
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CustomerGroupCollection $customerGroup
     */
    public function __construct(
        ProductFactory $connectorProductFactory,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $productCollectionFactory,
        CustomerGroupCollection $customerGroup
    ) {
        $this->connectorProductFactory = $connectorProductFactory;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->customerGroup = $customerGroup;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Export catalog
     *
     * @param int|null $storeId
     * @param array $productsToProcess
     *
     * @return array
     */
    public function exportCatalog(?int $storeId, array $productsToProcess): array
    {
        $connectorProducts = [];
        try {
            $products = $this->getProductsToExport($storeId, $productsToProcess);
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage());
            return [];
        }

        foreach ($products as $product) {
            try {
                $connectorProduct = $this->connectorProductFactory->create();
                $connectorProduct->setProduct($product, $storeId);
                $connectorProducts[$product->getId()] = $connectorProduct->toArray();
            } catch (SchemaValidationException $exception) {
                $this->logger->debug(
                    sprintf(
                        "Product id %s was not exported, but will be marked as processed.",
                        $product->getId()
                    ),
                    [$exception, $exception->errors()]
                );
            } catch (\Exception $exception) {
                $this->logger->debug(
                    sprintf(
                        'Product id %s was not exported, but will be marked as processed.',
                        $product->getId()
                    ),
                    [$exception]
                );
            }
        }
        return $connectorProducts;
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
    private function getProductsToExport(?int $storeId, array $productIds)
    {
        $collection = $this->filterProductsByStoreTypeAndVisibility(
            $storeId,
            $productIds,
            $this->getAllowedProductTypes($storeId),
            $this->getAllowedProductVisibilities($storeId)
        );

        $this->joinIndexedPrices($collection, $storeId);

        return $collection;
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
    ) {
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
    private function joinIndexedPrices(ProductCollection $productCollection, ?int $storeId)
    {
        if (!$this->scopeConfig->isSetFlag(
            Config::XML_PATH_CONNECTOR_SYNC_CATALOG_PRICE_RULES_ENABLED,
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
                    "rule_pricing_" . $groupId => 'final_price',
                    "rule_pricing_group_name_" . $groupId => new \Zend_Db_Expr("'$groupLabel'")
                ]
            );

        }

        return $productCollection;
    }
}
