<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData;

use Dotdigitalgroup\Email\Logger\Logger;
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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Delete constructor.
     *
     * @param SerializerInterface $serializer
     * @param SingleItemPostProcessorFactory $postProcessor
     * @param Logger $logger
     * @param array $data
     */
    public function __construct(
        SerializerInterface $serializer,
        SingleItemPostProcessorFactory $postProcessor,
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
     * @return void
     */
    public function process($item)
    {
        $importData = $this->serializer->unserialize($item->getImportData());
        $key = $importData[0];
        $this->client->deleteContactsTransactionalData($key, $item->getImportType());
    }
}
