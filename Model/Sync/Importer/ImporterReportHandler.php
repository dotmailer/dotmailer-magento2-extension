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
     * ImporterReportHandler constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ImporterFactory $importerFactory
     * @param ContactCollectionFactory $contactCollectionFactory
     * @param Contact $contactResource
     * @param DateTime $dateTime
     * @param CronOffsetter $cronOffsetter
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ImporterFactory $importerFactory,
        ContactCollectionFactory $contactCollectionFactory,
        Contact $contactResource,
        DateTime $dateTime,
        CronOffsetter $cronOffsetter
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->importerFactory = $importerFactory;
        $this->contactCollectionFactory = $contactCollectionFactory;
        $this->contactResource = $contactResource;
        $this->dateTime = $dateTime;
        $this->cronOffsetter = $cronOffsetter;
    }

    /**
     * Process report faults for contacts sync.
     *
     * @param int $id
     * @param int $websiteId
     * @param Client $client
     * @throws \Exception
     */
    public function processContactImportReportFaults($id, $websiteId, $client)
    {
        $report = $client->getContactImportReportFaults($id);

        if ($report) {
            $reportData = $this->prepareReportData($report);

            if (!empty($reportData)) {
                $contacts = $this->filterContactsInReportCsv($reportData);
                $lastSyncTime = $this->getLastContactSyncTime();

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
     * Get the time of the last contact sync.
     *
     * @return \DateTime
     * @throws \Exception
     */
    private function getLastContactSyncTime()
    {
        // get a time period for the last contact sync
        $cronMinutes = filter_var(
            $this->cronOffsetter->getDecodedCronValue(
                $this->scopeConfig->getValue(Config::XML_PATH_CRON_SCHEDULE_CONTACT),
            ),
            FILTER_SANITIZE_NUMBER_INT
        );
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
