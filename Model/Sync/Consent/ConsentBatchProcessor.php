<?php

namespace Dotdigitalgroup\Email\Model\Sync\Consent;

use Dotdigital\V3\Models\Collection;
use Dotdigital\V3\Models\ContactCollection;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigital\Exception\ResponseValidationException;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Consent;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;

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
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param ClientFactory $clientFactory
     * @param ImporterFactory $importerFactory
     * @param Logger $logger
     * @param Consent $consentResource
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ClientFactory $clientFactory,
        ImporterFactory $importerFactory,
        Logger $logger,
        Consent $consentResource,
        SerializerInterface $serializer
    ) {
        $this->clientFactory = $clientFactory;
        $this->importerFactory = $importerFactory;
        $this->logger = $logger;
        $this->consentResource = $consentResource;
        $this->serializer = $serializer;
    }

    /**
     * Process.
     *
     * @param Collection $dotdigitalCollection
     * @param mixed $websiteId
     * @param array $consentIds
     * @return void
     * @throws LocalizedException
     */
    public function process(Collection $dotdigitalCollection, $websiteId, array $consentIds)
    {
        if (!$dotdigitalCollection->count()) {
            return;
        }

        try {
            $importId = $this->pushBatch($dotdigitalCollection, $websiteId);
            if ($importId) {
                $this->addInProgressBatchToImportTable($dotdigitalCollection->all(), $websiteId, $importId);
            }
        } catch (ResponseValidationException $e) {
            $this->logger->debug(
                sprintf(
                    '%s: %s.',
                    'Error when pushing consent batch',
                    $e->getMessage()
                ),
                [$e->getDetails()]
            );
            $this->addFailedBatchToImportTable(
                $dotdigitalCollection->all(),
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
     * @param ContactCollection $dotdigitalCollection
     * @param string|int $websiteId
     * @return string
     */
    private function pushBatch(Collection $dotdigitalCollection, $websiteId):string
    {
        $response = $this->clientFactory
            ->create(['data' => ['websiteId' => $websiteId]])
            ->contacts
            ->import($dotdigitalCollection);

        return $this->getImportIdFromResponse($response);
    }

    /**
     * Get import id from serialized JSON.
     *
     * @param string $response
     *
     * @return string
     */
    private function getImportIdFromResponse($response)
    {
        try {
            $responseData = $this->serializer->unserialize($response);
            return $responseData['importId'] ?? '';
        } catch (\InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());
            return '';
        }
    }

    /**
     * Mark as imported.
     *
     * @param array $consentIds
     * @return void
     * @throws LocalizedException
     */
    private function markAsImported(array $consentIds)
    {
        $this->consentResource->setConsentRecordsImportedByIds($consentIds);
    }

    /**
     * Add batch to importer as 'Importing'.
     *
     * @param array $batch
     * @param string|int $websiteId
     * @param string $importId
     *
     * @return void
     */
    private function addInProgressBatchToImportTable(array $batch, $websiteId, string $importId)
    {
        $this->importerFactory->create()
            ->registerQueue(
                Importer::MODE_CONSENT,
                $batch,
                Importer::MODE_BULK,
                $websiteId,
                false,
                0,
                Importer::IMPORTING,
                $importId
            );
    }

    /**
     * Add failed batch to import table.
     *
     * @param array $batch
     * @param string|int $websiteId
     * @param string $message
     * @return void
     */
    private function addFailedBatchToImportTable(
        array $batch,
        $websiteId,
        string $message = ''
    ) {
        $this->importerFactory->create()
            ->registerQueue(
                Importer::MODE_CONSENT,
                $batch,
                Importer::MODE_BULK,
                $websiteId,
                false,
                0,
                Importer::FAILED,
                '',
                $message
            );
    }
}
