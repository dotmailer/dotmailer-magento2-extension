<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\BulkItemPostProcessorFactory;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Handle TD bulk data for importer.
 */
class Bulk extends AbstractItemSyncer
{
    /**
     * @var BulkItemPostProcessorFactory
     */
    protected $postProcessor;

    /**
     * Bulk constructor.
     *
     * @param Data $helper
     * @param File $fileHelper
     * @param SerializerInterface $serializer
     * @param Importer $importerResource
     * @param BulkItemPostProcessorFactory $postProcessor
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Data $helper,
        File $fileHelper,
        SerializerInterface $serializer,
        Importer $importerResource,
        BulkItemPostProcessorFactory $postProcessor,
        Logger $logger,
        array $data = []
    ) {
        $this->postProcessor = $postProcessor;
        parent::__construct($helper, $fileHelper, $serializer, $importerResource, $logger, $data);
    }

    /**
     * Process.
     *
     * @param mixed $item
     *
     * @return null
     * @throws \Exception
     */
    public function process($item)
    {
        $importData = $this->serializer->unserialize($item->getImportData());

        if (strpos($item->getImportType(), 'Catalog_') !== false) {
            $result = $this->client->postAccountTransactionalDataImport(
                $importData,
                $item->getImportType()
            );
        } else {
            $result = $this->client->postContactsTransactionalDataImport(
                $importData,
                $item->getImportType()
            );
        }

        return $result;
    }
}
