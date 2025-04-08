<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

use Magento\Framework\Exception\NoSuchEntityException;

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
     * Sync by store.
     *
     * @param array $productsToProcess
     * @param int|null $storeId
     * @param int $websiteId
     * @param string $catalogName
     *
     * @return array[]
     * @throws NoSuchEntityException
     */
    public function syncByStore(array $productsToProcess, ?int $storeId, int $websiteId, string $catalogName)
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
