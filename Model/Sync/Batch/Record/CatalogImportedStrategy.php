<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Record;

use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Record\RecordImportedStrategyInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory as CatalogResourceFactory;

/**
 * Class CatalogImportedStrategy
 *
 * This class implements the RecordImportedStrategyInterface and provides methods to set record IDs
 * and process the imported products.
 */
class CatalogImportedStrategy implements RecordImportedStrategyInterface
{
    /**
     * @var array
     */
    private $records = [];

    /**
     * @var CatalogResourceFactory
     */
    private $catalogResourceFactory;

    /**
     * CatalogImportedStrategy constructor.
     *
     * @param CatalogResourceFactory $catalogResourceFactory
     */
    public function __construct(
        CatalogResourceFactory $catalogResourceFactory
    ) {
        $this->catalogResourceFactory = $catalogResourceFactory;
    }

    /**
     * Set the records for the batch.
     *
     * @param array $records
     * @return CatalogImportedStrategy
     */
    public function setRecords(array $records): CatalogImportedStrategy
    {
        $this->records = $records;
        return $this;
    }

    /**
     * Process the imported products.
     *
     * @return void
     */
    public function process(): void
    {
        $this->catalogResourceFactory->create()
        ->setImportedDateByIds(
            array_keys($this->records)
        );
    }
}
