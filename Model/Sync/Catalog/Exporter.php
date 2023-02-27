<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Connector\ProductFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\SchemaValidationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Exporter
{
    /**
     * @var ProductFactory
     */
    private $connectorProductFactory;

    /**
     * @var CollectionFactory
     */
    private $catalogCollectionFactory;

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
     * @param CollectionFactory $catalogCollectionFactory
     * @param ProductFactory $connectorProductFactory
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $catalogCollectionFactory,
        ProductFactory $connectorProductFactory,
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->catalogCollectionFactory = $catalogCollectionFactory;
        $this->connectorProductFactory = $connectorProductFactory;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Export catalog
     *
     * @param string|int|null $storeId
     * @param array $productsToProcess
     * @return array
     */
    public function exportCatalog($storeId, $productsToProcess)
    {
        $connectorProducts = [];
        $products = $this->getProductsToExport($storeId, $productsToProcess);

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
     * @param string|int|null $storeId
     * @param array $productIds
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection|array
     */
    private function getProductsToExport($storeId, $productIds)
    {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();

        return $this->catalogCollectionFactory->create()
            ->filterProductsByStoreTypeAndVisibility(
                $storeId,
                $productIds,
                $this->getAllowedProductTypes($websiteId),
                $this->getAllowedProductVisibilities($websiteId)
            );
    }

    /**
     * Get allowed product types.
     *
     * @param int $websiteId
     *
     * @return array
     */
    private function getAllowedProductTypes(int $websiteId): array
    {
        $types = explode(
            ',',
            $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_SYNC_CATALOG_TYPE,
                ScopeInterface::SCOPE_WEBSITE,
                $websiteId
            )
        );
        return array_filter($types);
    }

    /**
     * Get allowed product visibilities.
     *
     * @param int $websiteId
     *
     * @return array
     */
    private function getAllowedProductVisibilities(int $websiteId): array
    {
        $visibilities = explode(
            ',',
            $this->scopeConfig->getValue(
                Config::XML_PATH_CONNECTOR_SYNC_CATALOG_VISIBILITY,
                ScopeInterface::SCOPE_WEBSITE,
                $websiteId
            )
        );
        return array_filter($visibilities);
    }
}
