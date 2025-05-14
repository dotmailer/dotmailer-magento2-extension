<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Sender;

use Dotdigital\V3\Models\InsightData;
use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Sender\SenderStrategyInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImportResponseHandler;
use Http\Client\Exception;

/**
 * Class CatalogSenderStrategy
 *
 * This class implements the SenderStrategyInterface and provides methods to set batch data,
 * website ID, and process catalog data for synchronization with Dotdigital.
 */
class CatalogSenderStrategy implements SenderStrategyInterface
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
     * @var string
     */
    private $catalogName;

    /**
     * @param ClientFactory $clientFactory
     * @param Logger $logger
     * @param ImportResponseHandler $importResponseHandler
     * @param string $catalogName
     */
    public function __construct(
        ClientFactory $clientFactory,
        Logger $logger,
        ImportResponseHandler $importResponseHandler,
        string $catalogName
    ) {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->importResponseHandler = $importResponseHandler;
        $this->catalogName = $catalogName;
    }

    /**
     * @inheritDoc
     */
    public function setBatch(array $batch): CatalogSenderStrategy
    {
        $this->batch = $batch;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setWebsiteId(int $websiteId): CatalogSenderStrategy
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
                'collectionName' => $this->catalogName,
                'collectionScope' => 'account',
                'collectionType' => 'catalog',
                'records' => $this->batch,
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
