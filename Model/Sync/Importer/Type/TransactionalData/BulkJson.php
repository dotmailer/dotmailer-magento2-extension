<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type\TransactionalData;

use Dotdigital\V3\Models\InsightData\Record;
use Dotdigitalgroup\Email\Api\Model\Sync\Importer\BulkSyncInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Sync\Batch\Sender\SenderStrategyFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\V3ItemPostProcessorFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\Type\AbstractItemSyncer;
use Magento\Framework\Serialize\SerializerInterface;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;

class BulkJson extends AbstractItemSyncer implements BulkSyncInterface
{
    /**
     * @var V3ItemPostProcessorFactory
     */
    protected $postProcessor;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var SenderStrategyFactory
     */
    protected $senderStrategyFactory;

    /**
     * BulkJson constructor.
     *
     * @param V3ItemPostProcessorFactory $postProcessor Factory for creating post-processor instances.
     * @param SerializerInterface $serializer Serializer for handling import data.
     * @param SenderStrategyFactory $senderStrategyFactory Factory for creating sender strategy instances.
     * @param Logger $logger Logger instance for logging.
     * @param array $data Additional data for the constructor.
     */
    public function __construct(
        V3ItemPostProcessorFactory $postProcessor,
        SerializerInterface $serializer,
        SenderStrategyFactory $senderStrategyFactory,
        Logger $logger,
        array $data = []
    ) {
        $this->postProcessor = $postProcessor;
        $this->serializer = $serializer;
        $this->senderStrategyFactory = $senderStrategyFactory;
        parent::__construct($logger, $data);
    }

    /**
     * Process a single importer item.
     *
     * @param ImporterModel $item The importer item to process.
     * @return mixed The result of the processing
     *
     * @throws \Exception
     */
    public function process($item)
    {
        return $this->senderStrategyFactory->create($item->getImportType())
            ->setBatch(array_map(function ($record) {
                return new Record($record);
            }, $this->serializer->unserialize($item->getImportData())))
            ->setWebsiteId((int) $item->getWebsiteId())
            ->process();
    }
}
