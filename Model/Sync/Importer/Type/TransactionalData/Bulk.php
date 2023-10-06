<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData;

use Dotdigitalgroup\Email\Logger\Logger;
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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Bulk constructor.
     *
     * @param SerializerInterface $serializer
     * @param BulkItemPostProcessorFactory $postProcessor
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        SerializerInterface $serializer,
        BulkItemPostProcessorFactory $postProcessor,
        Logger $logger,
        array $data = []
    ) {
        $this->postProcessor = $postProcessor;
        $this->serializer = $serializer;
        parent::__construct($logger, $data);
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
