<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData;

use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\BulkItemPostProcessorFactory;

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
     * @param \Dotdigitalgroup\Email\Helper\Data $helper
     * @param \Dotdigitalgroup\Email\Helper\File $fileHelper
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource
     * @param BulkItemPostProcessorFactory $postProcessor
     * @param array $data
     */
    public function __construct(
        \Dotdigitalgroup\Email\Helper\Data $helper,
        \Dotdigitalgroup\Email\Helper\File $fileHelper,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource,
        BulkItemPostProcessorFactory $postProcessor,
        array $data = []
    ) {
        $this->postProcessor = $postProcessor;

        parent::__construct($helper, $fileHelper, $serializer, $importerResource, $data);
    }

    /**
     * Process.
     *
     * @param mixed $item
     *
     * @return null
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
