<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Sender;

use Dotdigital\V3\Models\ContactCollection as DotdigitalContactCollection;
use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Sender\SenderStrategyInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImportResponseHandler;

class ContactSenderStrategy implements SenderStrategyInterface
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
    public function setBatch(array $batch): ContactSenderStrategy
    {
        $this->batch = $batch;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setWebsiteId(int $websiteId): ContactSenderStrategy
    {
        $this->websiteId = $websiteId;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function process(): string
    {
        $importId = '';

        $contactCollection = new DotdigitalContactCollection(
            array_values($this->batch)
        );
        $contactsResource = $this->clientFactory->create(['data' => ['websiteId' => $this->websiteId]])->contacts;
        $importResponse = $contactsResource->import($contactCollection);

        if ($importResponse) {
            $importId = $this->importResponseHandler->getImportIdFromResponse($importResponse);
            if ($importId) {
                $this->logger->info(
                    sprintf('Import id %s pushed to Dotdigital', $importId)
                );
            }
        }

        return $importId;
    }
}
