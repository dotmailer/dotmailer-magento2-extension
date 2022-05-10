<?php

namespace Dotdigitalgroup\Email\Model\Integration\Data;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory as CatalogCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog\Exporter;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

class Products
{
    private const NUMBER_OF_PRODUCTS = 10;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var CatalogCollectionFactory
     */
    private $catalogCollectionFactory;

    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Emulation
     */
    private $appEmulation;

    /**
     * @param Logger $logger
     * @param Data $helper
     * @param CatalogCollectionFactory $catalogCollectionFactory
     * @param Exporter $exporter
     * @param StoreManagerInterface $storeManager
     * @param Emulation $appEmulation
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        CatalogCollectionFactory $catalogCollectionFactory,
        Exporter $exporter,
        StoreManagerInterface $storeManager,
        Emulation $appEmulation
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->catalogCollectionFactory = $catalogCollectionFactory;
        $this->exporter = $exporter;
        $this->storeManager = $storeManager;
        $this->appEmulation = $appEmulation;
    }

    /**
     * Prepare data and send.
     *
     * @param int $websiteId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepareAndSend(int $websiteId)
    {
        $productsToProcess = $this->catalogCollectionFactory->create()
            ->getProducts(self::NUMBER_OF_PRODUCTS);

        if (!$productsToProcess) {
            $this->logger->debug('No products available in email_catalog');
            return false;
        }

        $catalogs = [];
        /** @var \Magento\Store\Model\Website $website */
        $website = $this->storeManager->getWebsite($websiteId);

        foreach ($website->getStores() as $store) {
            $this->appEmulation->startEnvironmentEmulation(
                $store->getId(),
                Area::AREA_FRONTEND,
                true
            );

            $catalogName = 'Catalog_' . $store->getWebsite()->getCode() . '_' . $store->getCode();
            $products = $this->exporter->exportCatalog($store->getId(), $productsToProcess);
            $catalogs[$catalogName] = [
                'products' => $products,
                'websiteId' => $websiteId
            ];

            $this->appEmulation->stopEnvironmentEmulation();
        }

        return $this->sendProducts($catalogs, $websiteId);
    }

    /**
     * Send products to Dotdigital.
     *
     * If a catalog batch has no products, it's likely there were errors (and caught exceptions)
     * in the exporter, so we fail the whole operation. Equally if we have even a single failed
     * transactional data post, we return false.
     *
     * @param array $catalogs
     * @param int $websiteId
     *
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendProducts(array $catalogs, int $websiteId)
    {
        $client = $this->helper->getWebsiteApiClient($websiteId);
        if (!$client) {
            $this->logger->debug(sprintf('API client error for website %d', $websiteId));
            return false;
        }

        foreach ($catalogs as $catalogName => $batch) {
            if (empty($batch['products'])) {
                $this->logger->debug(
                    sprintf('No products could be synced for catalog %s', $catalogName)
                );
                return false;
            }

            foreach ($batch['products'] as $product) {
                $result = $client->postAccountTransactionalData($product, $catalogName);
                if (isset($result->message)) {
                    return false;
                }
            }

            $this->logger->info(sprintf(
                '%d products posted for catalog %s',
                count($batch['products']),
                $catalogName
            ));
        }

        return true;
    }
}
