<?php

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigital\V3\Models\ContactFactory as DotdigitalContactFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Consent\CollectionFactory as ConsentCollectionFactory;
use Dotdigital\V3\Models\ContactCollectionFactory as DotdigitalCollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Consent\ConsentBatchProcessor;
use Dotdigitalgroup\Email\Model\ResourceModel\Consent\Collection as ConsentCollection;
use Dotdigital\V3\Models\ContactCollection;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Consent implements SyncInterface
{
    private const CONSENT_RECORD_IMPORT_LIMIT = 5;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var DotdigitalContactFactory
     */
    private $sdkContactFactory;

    /**
     * @var ConsentCollectionFactory
     */
    private $consentCollectionFactory;

    /**
     * @var DotdigitalCollectionFactory
     */
    private $sdkContactCollectionFactory;

    /**
     * @var ConsentBatchProcessor
     */
    private $consentBatchProcessor;

    /**
     * @var int
     */
    private $consentRecordsSyncedCount = 0;

    /**
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param DotdigitalContactFactory $sdkContactFactory
     * @param ConsentCollectionFactory $consentCollectionFactory
     * @param DotdigitalCollectionFactory $sdkContactCollectionFactory
     * @param ConsentBatchProcessor $consentBatchProcessor
     */
    public function __construct(
        Logger $logger,
        ScopeConfigInterface $scopeConfig,
        Data $helper,
        DotdigitalContactFactory $sdkContactFactory,
        ConsentCollectionFactory $consentCollectionFactory,
        DotdigitalCollectionFactory $sdkContactCollectionFactory,
        ConsentBatchProcessor $consentBatchProcessor
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->sdkContactFactory = $sdkContactFactory;
        $this->consentCollectionFactory = $consentCollectionFactory;
        $this->sdkContactCollectionFactory = $sdkContactCollectionFactory;
        $this->consentBatchProcessor = $consentBatchProcessor;
    }

    /**
     * Sync.
     *
     * @param \DateTime|null $from
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function sync(\DateTime $from = null)
    {
        $start = microtime(true);

        $megaBatchSize = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_MEGA_BATCH_SIZE_CONTACT
        );

        $breakValue = (int) $this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_SYNC_BREAK_VALUE);
        $websiteIds = $this->consentCollectionFactory->create()->getWebsitesToSync();
        foreach ($websiteIds as $websiteId) {
            $apiEnabled = $this->helper->isEnabled($websiteId);
            if ($apiEnabled) {
                try {
                    $this->loopByWebsite(
                        $websiteId,
                        $megaBatchSize,
                        $breakValue
                    );
                } catch (\Exception $e) {
                    $this->logger->error(
                        sprintf(
                            'Error in consent sync for website id: %d',
                            $websiteId
                        ),
                        [(string)$e]
                    );
                }
            }
        }

        $message = '----------- Consent sync ----------- : '
            . gmdate('H:i:s', (int) (microtime(true) - $start))
            . ', Total synced = ' . $this->consentRecordsSyncedCount;

        if ($this->consentRecordsSyncedCount > 0 || $this->helper->isDebugEnabled()) {
            $this->logger->info($message);
        }
    }

    /**
     * Loop by website.
     *
     * @param mixed $websiteId
     * @param int $megaBatchSize
     * @param int $breakValue
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    private function loopByWebsite($websiteId, int $megaBatchSize, int $breakValue)
    {
        $offset = 0;
        $syncedRecordsBatchSize = 0;
        $consentIds = [];
        $dotdigitalCollection = $this->sdkContactCollectionFactory->create();

        $limit = (int) $this->scopeConfig->getValue(
            Config::XML_PATH_CONNECTOR_SYNC_LIMIT,
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        do {
            $consentRecords = $this->consentCollectionFactory->create()
                ->getConsentRecordsToSync($limit, $offset, $websiteId);

            if (count($consentRecords) === 0) {
                break;
            }

            $offset += count($consentRecords);

            $exportedData = $this->export($dotdigitalCollection, $consentRecords);
            $consentIds = array_merge($consentIds, $exportedData['consentIds']);
            $syncedRecords = $exportedData['syncedRecords'];
            $this->consentRecordsSyncedCount += $syncedRecords;
            $syncedRecordsBatchSize += $syncedRecords;

            if ($syncedRecordsBatchSize >= $megaBatchSize) {
                $this->consentBatchProcessor->process($dotdigitalCollection, $websiteId, $consentIds);
                $dotdigitalCollection = $this->sdkContactCollectionFactory->create();
                $offset = 0;
                $syncedRecordsBatchSize = 0;
                $consentIds = [];
            }

        } while (!$breakValue || $this->consentRecordsSyncedCount < $breakValue);

        $this->consentBatchProcessor->process($dotdigitalCollection, $websiteId, $consentIds);
    }

    /**
     * Export.
     *
     * @param ContactCollection $dotdigitalCollection
     * @param ConsentCollection $consentCollection
     * @return array
     * @throws \Exception
     */
    private function export(ContactCollection &$dotdigitalCollection, ConsentCollection $consentCollection): array
    {
        $consentData = [];
        $consentIds = [];
        $syncedRecords = 0;

        foreach ($consentCollection as $consent) {
            if (isset($consentData[$consent->getEmail()]) &&
                count($consentData[$consent->getEmail()]) >= self::CONSENT_RECORD_IMPORT_LIMIT
            ) {
                continue;
            }
            $consentData[$consent->getEmail()][$consent->getId()] = [
                'text' => $consent->getConsentText(),
                'dateTimeConsented' => $consent->getConsentDatetime(),
                'url' => $consent->getConsentUrl(),
                'ipAddress' => $consent->getConsentIp(),
                'userAgent' => $consent->getConsentUserAgent()
            ];
        }

        foreach ($consentData as $email => $consentRecords) {
            try {
                $contact = $this->sdkContactFactory->create();
                $contact->setMatchIdentifier('email');
                $contact->setIdentifiers(['email' => $email]);
                $contact->setConsentRecords($consentRecords);
                $dotdigitalCollection->add($contact);

                $this->addConsentIds($consentRecords, $consentIds);
                $syncedRecords += count($consentRecords);

            } catch (\Exception $e) {
                $this->logger->debug(
                    sprintf(
                        'Error exporting consent data for contact: %s',
                        $email
                    ),
                    [(string)$e]
                );
                continue;
            }
        }

        return [ 'consentIds' => $consentIds, 'syncedRecords' => $syncedRecords];
    }

    /**
     * Add consent ids to an array, preserving their keys.
     *
     * @param array $records
     * @param array $consentIds
     *
     * @return void
     */
    private function addConsentIds(array $records, array &$consentIds)
    {
        foreach ($records as $id => $record) {
            $consentIds[] = $id;
        }
    }
}
