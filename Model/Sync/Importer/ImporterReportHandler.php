<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Cron\CronOffsetter;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory as ImporterCollectionFactory;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Framework\Serialize\SerializerInterface;

class ImporterReportHandler
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CronOffsetter
     */
    private $cronOffsetter;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var Contact
     */
    public $contactResource;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var array
     */
    private $reasons
        = [
            'Globally Suppressed',
            'Blocked',
            'Unsubscribed',
            'Hard Bounced',
            'Isp Complaints',
            'Domain Suppressed',
            'Failures',
            'Invalid Entries',
            'Mail Blocked',
            'Suppressed by you',
        ];

    /**
     * @var ImporterCollectionFactory
     */
    private $importerCollectionFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ImporterFactory $importerFactory
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param Contact $contactResource
     * @param DateTime $dateTime
     * @param CronOffsetter $cronOffsetter
     * @param ImporterCollectionFactory $importerCollectionFactory
     * @param SerializerInterface $serializer
     * @param Logger $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ImporterFactory $importerFactory,
        ContactCollectionFactory $contactCollectionFactory,
        Contact $contactResource,
        DateTime $dateTime,
        CronOffsetter $cronOffsetter,
        ImporterCollectionFactory $importerCollectionFactory,
        SerializerInterface $serializer,
        Logger $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->importerFactory = $importerFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->dateTime = $dateTime;
        $this->cronOffsetter = $cronOffsetter;
        $this->importerCollectionFactory = $importerCollectionFactory;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * Process report faults for contacts sync.
     *
     * @param int $id
     * @param int $websiteId
     * @param string $importType
     * @param Client $client
     *
     * @return void
     * @throws \Exception
     */
    public function processContactImportReportFaults($id, $websiteId, $importType, $client)
    {
        $report = $client->getContactImportReportFaults($id);

        if ($report) {
            $reportData = $this->prepareReportData($report);

            if (!empty($reportData)) {
                $contacts = $this->filterContactsInReportCsv($reportData);
                $lastSyncTime = $this->getLastContactSyncTime($importType);

                $recordsToCheck = $this->contactCollectionFactory->create()
                    ->getContactsByEmailsAndWebsiteId($contacts, $websiteId)
                    ->getItems();

                $contactsToResubscribe = $contactsToUnsubscribe = [];

                foreach ($recordsToCheck as $contact) {
                    if (!$contact->getLastSubscribedAt() ||
                        !$contact->getIsSubscriber() ||
                        $contact->getSuppressed()) {
                        $contactsToUnsubscribe[] = $contact->getEmail();
                        continue;
                    }

                    $lastSubscribed = new \DateTime($contact->getLastSubscribedAt(), new \DateTimeZone('UTC'));
                    if ($lastSubscribed <= $lastSyncTime) {
                        $contactsToUnsubscribe[] = $contact->getEmail();
                        continue;
                    }

                    $contactsToResubscribe[] = $contact->getEmail();
                }

                $this->doResubscribes($contactsToResubscribe, $websiteId);
                $this->doUnsubscribes($contactsToUnsubscribe, $websiteId);
            }
        }
    }

    /**
     * Check for failed report items.
     *
     * @param string $id
     * @param string|int $websiteId
     * @param Client $client
     * @param string $importType
     * @return void
     */
    public function processInsightReportFaults($id, $websiteId, Client $client, string $importType)
    {
        $report = $client->getTransactionalDataReportById($id);

        if ($report) {
            if (isset($report->totalRejected) && $report->totalRejected > 0) {
                $this->requeueFailedItems($report, $id, $websiteId, $importType);
            }
        }
    }

    /**
     * Prepare failed items for re import.
     *
     * @param \stdClass $reportFaults
     * @param string $importId
     * @param string|int $websiteId
     * @param string $importType
     * @return void
     */
    private function requeueFailedItems($reportFaults, string $importId, $websiteId, string $importType)
    {
        $importerResult = $this->importerCollectionFactory->create()
            ->getImporterDataByImportId($importId);

        $importData = $this->serializer->unserialize(
            (string) $importerResult->getData('import_data')
        );

        $retryCount = (int) $importerResult->getData('retry_count');

        $toReImport = [];
        $faultsToLog = [];

        foreach ($reportFaults->faults as $fault) {
            $faultsToLog[] = (array) $fault;
            if (array_key_exists($fault->key, $importData) && $fault->reason == 'ContactEmailDoesNotExist') {
                $toReImport[$fault->key] = $importData[$fault->key];
            }
        }

        if ($this->scopeConfig->getValue(Config::XML_PATH_CONNECTOR_ADVANCED_DEBUG_ENABLED)) {
            $this->logger->debug(
                sprintf(
                    '%d faults for %s import (%s, retry %d)',
                    count($faultsToLog),
                    $importType,
                    $importId,
                    $retryCount
                ),
                $faultsToLog
            );
        }

        $this->importerFactory->create()
            ->registerQueue(
                $importType,
                $toReImport,
                \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                $websiteId,
                false,
                ++$retryCount
            );
    }

    /**
     * Convert utf8 data and strip csv headers.
     *
     * @param string $report
     * @return array
     */
    private function prepareReportData($report)
    {
        $bom = pack('H*', 'EFBBBF');
        $cleaned = preg_replace("/^$bom/", '', $report);
        $reportData = explode(PHP_EOL, $cleaned);

        // remove header row
        unset($reportData[0]);

        return $reportData;
    }

    /**
     * Filter contacts.
     *
     * @param array $data
     * @return array
     */
    private function filterContactsInReportCsv($data)
    {
        $contacts = [];
        foreach ($data as $row) {
            if (empty($row) || strpos($row, ',') === false) {
                continue;
            }

            list($reason, $email) = explode(',', $row);
            if (in_array($reason, $this->reasons)) {
                $contacts[] = $email;
            }
        }

        return $contacts;
    }

    /**
     * Get the last 'contact' sync time.
     *
     * Contact sync became 3 separate syncs, all with different timings.
     * This gets the time of the last customer, subscriber or guest sync,
     * depending on which type of import we are processing a finished item for.
     *
     * @param string $importType
     * @return \DateTime
     * @throws \Exception
     */
    private function getLastContactSyncTime($importType)
    {
        $syncTypes = [
            ImporterModel::IMPORT_TYPE_CONTACT => Config::XML_PATH_CRON_SCHEDULE_CUSTOMER,
            ImporterModel::IMPORT_TYPE_CUSTOMER => Config::XML_PATH_CRON_SCHEDULE_CUSTOMER,
            ImporterModel::IMPORT_TYPE_SUBSCRIBERS => Config::XML_PATH_CRON_SCHEDULE_SUBSCRIBER,
            ImporterModel::IMPORT_TYPE_GUEST => Config::XML_PATH_CRON_SCHEDULE_GUEST,
        ];

        $decodedCronValue = filter_var(
            $this->cronOffsetter->getDecodedCronValue(
                $this->scopeConfig->getValue($syncTypes[$importType])
            ),
            FILTER_SANITIZE_NUMBER_INT
        );
        $cronMinutes = ($decodedCronValue === '00' ? '60' : $decodedCronValue);
        $time = new \DateTime($this->dateTime->formatDate(true), new \DateTimeZone('UTC'));
        return $time->sub(new \DateInterval("PT{$cronMinutes}M"));
    }

    /**
     * Queue resubscription jobs
     *
     * @param array $contacts
     * @param string|int $websiteId
     */
    private function doResubscribes($contacts, $websiteId)
    {
        if (!empty($contacts)) {
            $importerModel = $this->importerFactory->create();

            foreach ($contacts as $resubscriber) {
                $importerModel->registerQueue(
                    ImporterModel::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED,
                    ['email' => $resubscriber],
                    ImporterModel::MODE_SUBSCRIBER_RESUBSCRIBED,
                    $websiteId
                );
            }
        }
    }

    /**
     * Unsubscribe any non-resubscribes
     *
     * @param array $contacts
     * @param string|int $websiteId
     */
    private function doUnsubscribes($contacts, $websiteId)
    {
        $this->contactResource->unsubscribeByWebsiteAndStore($contacts, [$websiteId]);
    }
}
