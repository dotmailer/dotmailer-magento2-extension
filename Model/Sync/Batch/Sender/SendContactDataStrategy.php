<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Sender;

use Dotdigital\Exception\ResponseValidationException;
use Dotdigital\V3\Models\ContactCollection as DotdigitalContactCollection;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImportResponseHandler;
use Http\Client\Exception;

class SendContactDataStrategy implements SendDataStrategyInterface
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
     * Send data to Dotdigital.
     *
     * @param array $batch
     * @param int $websiteId
     *
     * @return string
     * @throws ResponseValidationException
     * @throws Exception
     */
    public function sendDataToDotdigital(array $batch, int $websiteId): string
    {
        $importId = '';

        $contactCollection = new DotdigitalContactCollection(
            array_values($batch)
        );
        $contactsResource = $this->clientFactory->create(['data' => ['websiteId' => $websiteId]])->contacts;
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
