<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

class StoreCatalogSyncer
{
    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * StoreLevelCatalogSyncer constructor.
     *
     * @param Exporter $exporter
     */
    public function __construct(
        Exporter $exporter
    ) {
        $this->exporter = $exporter;
    }

    /**
     * @param array $productsToProcess
     * @param string|int|null $storeId
     * @param string|int $websiteId
     * @param string $catalogName
     * @return array[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function syncByStore(array $productsToProcess, $storeId, $websiteId, string $catalogName)
    {
        $products = $this->exporter->exportCatalog($storeId, $productsToProcess);

        return [
            $catalogName => [
                'products' => $products,
                'websiteId' => $websiteId
            ]
        ];
    }
}
