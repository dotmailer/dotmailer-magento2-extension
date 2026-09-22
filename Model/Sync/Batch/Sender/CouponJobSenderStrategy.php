<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Sender;

use Dotdigital\V3\Models\ContactCollection as DotdigitalContactCollection;
use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Sender\SenderStrategyInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Sync\Importer\ImportResponseHandler;

/**
 * Sender strategy for CouponJob contact-update batches.
 *
 * Functionally equivalent to ContactSenderStrategy but registered as a distinct
 * class so that coupon-job batches can diverge independently and are easily
 * identifiable in the pipeline.
 */
class CouponJobSenderStrategy implements SenderStrategyInterface
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
    public function setBatch(array $batch): CouponJobSenderStrategy
    {
        $this->batch = $batch;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setWebsiteId(int $websiteId): CouponJobSenderStrategy
    {
        $this->websiteId = $websiteId;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRecordCount(): int
    {
        return isset($this->batch['records']) && is_array($this->batch['records'])
            ? count($this->batch['records'])
            : 0;
    }

    /**
     * @inheritDoc
     */
    public function process(): string
    {
        $importId = '';

        $contactCollection = new DotdigitalContactCollection(array_values($this->batch['records']));
        $contactsResource = $this->clientFactory->create(['data' => ['websiteId' => $this->websiteId]])->contacts;
        $importResponse = $contactsResource->import($contactCollection);

        if ($importResponse) {
            $importId = $this->importResponseHandler->getImportIdFromResponse($importResponse);
            if ($importId) {
                $this->logger->info(
                    sprintf('CouponJob import id %s pushed to Dotdigital', $importId)
                );
            }
        }

        return $importId;
    }
}
