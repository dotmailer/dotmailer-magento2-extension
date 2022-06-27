<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\SingleItemPostProcessorFactory;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Handle TD delete data for importer.
 */
class Delete extends AbstractItemSyncer
{
    /**
     * @var SingleItemPostProcessorFactory
     */
    protected $postProcessor;

    /**
     * Delete constructor.
     *
     * @param Data $helper
     * @param File $fileHelper
     * @param SerializerInterface $serializer
     * @param Importer $importerResource
     * @param SingleItemPostProcessorFactory $postProcessor
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        Data $helper,
        File $fileHelper,
        SerializerInterface $serializer,
        Importer $importerResource,
        SingleItemPostProcessorFactory $postProcessor,
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
     * @return void
     */
    public function process($item)
    {
        $importData = $this->serializer->unserialize($item->getImportData());
        $key = $importData[0];
        $this->client->deleteContactsTransactionalData($key, $item->getImportType());
    }
}
