<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer\ReportHandler;

use Dotdigital\V3\Models\Contact\Import;
use Dotdigital\V3\Models\Import\ImportInterface as V3ImportInterface;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory as ContactResourceFactory;

class V3ImporterReportHandler
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ContactResourceFactory
     */
    private $contactResourceFactory;

    /**
     * @param Logger $logger
     * @param ContactResourceFactory $contactResourceFactory
     */
    public function __construct(
        Logger $logger,
        ContactResourceFactory $contactResourceFactory
    ) {
        $this->logger = $logger;
        $this->contactResourceFactory = $contactResourceFactory;
    }

    /**
     * Process.
     *
     * @param V3ImportInterface $response
     *
     * @return void
     *
     * @deprecated Broken up into smaller methods.
     * @see V3ImporterReportHandler::logSummary
     */
    public function process(V3ImportInterface $response)
    {
        if ($response->getSummary()) {
            $this->logger->info(
                sprintf(
                    'Import id %s finished, summary: %s',
                    $response->getImportId(),
                    json_encode($response->getSummary())
                )
            );
        }

        if ($response->getFailures()) {
            foreach ($response->getFailures() as $failure) {
                $this->logger->debug(
                    sprintf(
                        'Import id %s failure: %s',
                        $response->getImportId(),
                        json_encode($failure)
                    )
                );
            }
        }
    }

    /**
     * Log import summary.
     *
     * @param V3ImportInterface $response
     *
     * @return void
     */
    public function logSummary(V3ImportInterface $response): void
    {
        if ($response->getSummary()) {
            $this->logger->info(
                sprintf(
                    'Import id %s finished, summary: %s',
                    $response->getImportId(),
                    json_encode($response->getSummary())
                )
            );
        }
    }

    /**
     * Log import failures.
     *
     * @param V3ImportInterface $response
     *
     * @return void
     */
    public function logFailures(V3ImportInterface $response): void
    {
        if ($response->getFailures()) {
            foreach ($response->getFailures() as $failure) {
                $this->logger->debug(
                    sprintf(
                        'Import id %s failure: %s',
                        $response->getImportId(),
                        json_encode($failure)
                    )
                );
            }
        }
    }

    /**
     * Store contact ids from import response.
     *
     * @param V3ImportInterface $response
     * @param int $websiteId
     *
     * @return void
     */
    public function storeContactIds(V3ImportInterface $response, int $websiteId): void
    {
        $contactIds = [];
        /** @var Import $response */
        $created = $response->getCreated() ? $response->getCreated()->all() : [];
        $updated = $response->getUpdated() ? $response->getUpdated()->all() : [];
        $records = array_merge($created, $updated);

        foreach ($records as $record) {
            $email = $record->getIdentifiers()->getEmail();
            if ($email) {
                $contactIds[$email] = $record->getContactId();
            }
        }

        if (!empty($contactIds)) {
            try {
                $this->contactResourceFactory->create()
                    ->setContactIdsForEmailsByWebsiteId(
                        $contactIds,
                        $websiteId
                    );
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf(
                        'Error storing contact ids for website %s: %s',
                        $websiteId,
                        $e->getMessage()
                    )
                );
            }
        }
    }
}
