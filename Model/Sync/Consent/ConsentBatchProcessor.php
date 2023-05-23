<?php

namespace Dotdigitalgroup\Email\Model\Sync\Consent;

use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigital\Exception\ResponseValidationException;
use Http\Client\Exception;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Consent;

class ConsentBatchProcessor
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Consent
     */
    private $consentResource;

    /**
     * @param ClientFactory $clientFactory
     * @param ImporterFactory $importerFactory
     * @param Logger $logger
     * @param Consent $consentResource
     */
    public function __construct(
        ClientFactory $clientFactory,
        ImporterFactory $importerFactory,
        Logger $logger,
        Consent $consentResource
    ) {
        $this->clientFactory = $clientFactory;
        $this->importerFactory = $importerFactory;
        $this->logger = $logger;
        $this->consentResource = $consentResource;
    }

    /**
     * Process.
     *
     * @param \Dotdigital\V3\Models\Collection $dotdigitalCollection
     * @param mixed $websiteId
     * @param array $consentIds
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(\Dotdigital\V3\Models\Collection $dotdigitalCollection, $websiteId, array $consentIds)
    {
        if (!$dotdigitalCollection->count()) {
            return;
        }

        try {
            $importId = $this->pushBatch($dotdigitalCollection);
            if ($importId) {
                $this->addInProgressBatchToImportTable($dotdigitalCollection, $websiteId, $importId);
            }
        } catch (ResponseValidationException | Exception | \Exception $e) {
            $this->logger->debug((string) $e);
            $this->addFailedBatchToImportTable(
                $dotdigitalCollection,
                $websiteId,
                $e->getMessage()
            );
        } finally {
            $this->markAsImported($consentIds);
        }
    }

    /**
     * Push batch.
     *
     * @param \Dotdigital\V3\Models\Collection $dotdigitalCollection
     * @return string
     * @throws \Exception
     */
    private function pushBatch(\Dotdigital\V3\Models\Collection $dotdigitalCollection)
    {
        //Mocked push
        $this->logger->info(
            sprintf('Import id %s pushed to Dotdigital for consent', $importId = base64_encode(random_bytes(10)))
        );

        return $importId;
    }

    /**
     * Add in progress batch to import table.
     *
     * @param \Dotdigital\V3\Models\Collection $dotdigitalCollection
     * @param mixed $websiteId
     * @param string $importId
     * @return void
     */
    private function addInProgressBatchToImportTable(
        \Dotdigital\V3\Models\Collection $dotdigitalCollection,
        $websiteId,
        string $importId
    ) {
        $this->importerFactory->create()
            ->registerQueue(
                Importer::MODE_CONSENT,
                $dotdigitalCollection->toJson(),
                Importer::MODE_BULK,
                $websiteId,
                false,
                0,
                Importer::IMPORTING,
                $importId
            );
    }

    /**
     * Mark as imported.
     *
     * @param array $consentIds
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function markAsImported(array $consentIds)
    {
        $this->consentResource->setConsentRecordsImportedByIds($consentIds);
    }

    /**
     * Add failed batch to import table.
     *
     * @param \Dotdigital\V3\Models\Collection $dotdigitalCollection
     * @param mixed $websiteId
     * @param string $importId
     * @return void
     */
    private function addFailedBatchToImportTable(
        \Dotdigital\V3\Models\Collection $dotdigitalCollection,
        $websiteId,
        string $importId
    ) {
        $this->importerFactory->create()
            ->registerQueue(
                Importer::MODE_CONSENT,
                $dotdigitalCollection->toJson(),
                Importer::MODE_BULK,
                $websiteId,
                false,
                0,
                Importer::FAILED,
                $importId
            );
    }
}
