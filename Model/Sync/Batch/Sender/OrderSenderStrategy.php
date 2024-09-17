<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Sender;

use Dotdigital\V3\Models\InsightData;
use Dotdigital\V3\Models\InsightData\RecordsCollection;
use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Sender\SenderStrategyInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImportResponseHandler;
use Http\Client\Exception;

class OrderSenderStrategy implements SenderStrategyInterface
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ImportResponseHandler
     */
    private $importResponseHandler;

    /**
     * @var array
     */
    private $batch;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * @param ClientFactory $clientFactory
     * @param Logger $logger
     * @param ImportResponseHandler $importResponseHandler
     */
    public function __construct(
        ClientFactory $clientFactory,
        Logger $logger,
        ImportResponseHandler $importResponseHandler
    ) {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->importResponseHandler = $importResponseHandler;
    }

    /**
     * @inheritDoc
     */
    public function setBatch(array $batch): OrderSenderStrategy
    {
        $this->batch = $batch;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setWebsiteId(int $websiteId): OrderSenderStrategy
    {
        $this->websiteId = $websiteId;
        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws \Exception
     * @throws Exception
     */
    public function process(): string
    {
        $importResponse = $this->clientFactory->create(['data' => ['websiteId' => $this->websiteId]])
            ->insightData
            ->import(new InsightData([
                'collectionName' => 'Orders',
                'collectionScope' => 'contact',
                'collectionType' => 'orders',
                'records' => $this->batch
            ]));

        if ($importResponse) {
            $importId = $this->importResponseHandler->getImportIdFromResponse($importResponse);
            if ($importId) {
                $this->logger->info(
                    sprintf('Import id %s pushed to Dotdigital', $importId)
                );
            }
        }

        return $importId ?? '';
    }
}
