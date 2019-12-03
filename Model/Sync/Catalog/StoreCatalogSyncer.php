<?php

namespace Dotdigitalgroup\Email\Model\Sync\Catalog;

class StoreCatalogSyncer
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    private $importerFactory;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * StoreLevelCatalogSyncer constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param Exporter $exporter
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\Data $helper,
        Exporter $exporter
    ) {
        $this->importerFactory = $importerFactory;
        $this->helper = $helper;
        $this->exporter = $exporter;
    }

    /**
     * Sync by store
     *
     * @param array $productsToProcess
     * @param string $storeId
     * @param string $websiteId
     * @param string $importType
     *
     * @return array
     */
    public function syncByStore($productsToProcess, $storeId, $websiteId, $importType)
    {
        $products = $this->exporter->exportCatalog($storeId, $productsToProcess);

        if ($products) {
            $success = $this->importerFactory->create()
                ->registerQueue(
                    $importType,
                    $products,
                    \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                    $websiteId
                );

            if ($success) {
                return $products;
            } else {
                $pid = implode(",", array_keys($products));
                $msg = "Failed to register with IMPORTER. Type(Catalog) / Scope(Bulk) / Store($storeId) / Product Ids($pid)";
                $this->helper->log($msg);
            }
        }

        return [];
    }
}
