<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\DataObject;

class ImporterProgressHandler extends DataObject
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var File
     */
    private $fileHelper;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Client
     */
    private $client;

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
     * @var array
     */
    private $importStatuses
        = [
            'RejectedByWatchdog',
            'InvalidFileFormat',
            'Unknown',
            'Failed',
            'ExceedsAllowedContactLimit',
            'NotAvailableInThisVersion',
        ];

    /**
     * Importer constructor.
     *
     * @param Data $helper
     * @param File $fileHelper
     * @param ImporterFactory $importerFactory
     * @param DateTime $dateTime
     * @param array $data
     */
    public function __construct(
        Data $helper,
        File $fileHelper,
        ImporterFactory $importerFactory,
        DateTime $dateTime,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->fileHelper = $fileHelper;
        $this->importerFactory = $importerFactory;
        $this->dateTime = $dateTime;

        parent::__construct($data);
    }

    /**
     * Check imports in progress for an array of website ids.
     * Note this will only pick up bulk imports.
     *
     * @param array $websiteIds
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkImportsInProgress($websiteIds)
    {
        $itemCount = 0;
        $importerModel = $this->importerFactory->create();
        $items = $importerModel->_getImportingItems($websiteIds);
        if (!$items) {
            return $itemCount;
        }

        $this->client = $this->getClient();

        foreach ($items as $item) {
            try {
                if ($item->getImportType() == ImporterModel::IMPORT_TYPE_CONTACT ||
                    $item->getImportType() == ImporterModel::IMPORT_TYPE_SUBSCRIBERS ||
                    $item->getImportType() == ImporterModel::IMPORT_TYPE_GUEST
                ) {
                    $response = $this->client->getContactsImportByImportId($item->getImportId());
                } else {
                    $response = $this->client->getContactsTransactionalDataImportByImportId(
                        $item->getImportId()
                    );
                }
            } catch (\Exception $e) {
                $item->setMessage($e->getMessage())
                    ->setImportStatus(ImporterModel::FAILED);
                $importerModel->saveItem($item);
                continue;
            }

            $itemCount += $this->processResponse($response, $item);
        }

        return $itemCount;
    }

    /**
     * @param Object $response
     * @param ImporterModel $item
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processResponse($response, $item)
    {
        $itemCount = 0;
        if (isset($response->message)) {
            $item->setImportStatus(ImporterModel::FAILED)
                ->setMessage($response->message);
        } else {
            if ($response->status == 'Finished') {
                $item = $this->processFinishedItem($item);
            } elseif (in_array($response->status, $this->importStatuses)) {
                $item->setImportStatus(ImporterModel::FAILED)
                    ->setMessage('Import failed with status ' . $response->status);
            } else {
                //Not finished
                $itemCount = 1;
            }
        }
        //Save item
        $this->importerFactory->create()
            ->saveItem($item);

        return $itemCount;
    }

    /**
     * @param ImporterModel $item
     * @return ImporterModel $item
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processFinishedItem($item)
    {
        $now = gmdate('Y-m-d H:i:s');

        $item->setImportStatus(ImporterModel::IMPORTED)
            ->setImportFinished($now)
            ->setMessage('');

        if ($item->getImportType() == ImporterModel::IMPORT_TYPE_CONTACT ||
            $item->getImportType() == ImporterModel::IMPORT_TYPE_SUBSCRIBERS ||
            $item->getImportType() == ImporterModel::IMPORT_TYPE_GUEST
        ) {
            $file = $item->getImportFile();
            // if a filename is stored in the table and if that file physically exists
            if ($file && $this->fileHelper->isFilePathExistWithFallback($file)) {
                //remove the consent data for contacts before archiving the file
                $log = $this->fileHelper->cleanProcessedConsent(
                    $this->fileHelper->getFilePathWithFallback($file)
                );
                if ($log) {
                    $this->helper->log($log);
                }
                if (! $this->fileHelper->isFileAlreadyArchived($file)) {
                    $this->fileHelper->archiveCSV($file);
                }
            }

            if ($item->getImportId()) {
                $this->_processContactImportReportFaults($item->getImportId(), $item->getWebsiteId());
            }
        }

        return $item;
    }

    /**
     * Get report info for contacts sync.
     *
     * @param int $id
     * @param int $websiteId
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _processContactImportReportFaults($id, $websiteId)
    {
        $report = $this->client->getContactImportReportFaults($id);

        if ($report) {
            $reportData = explode(PHP_EOL, $this->_removeUtf8Bom($report));
            //unset header
            unset($reportData[0]);
            //no data in report
            if (! empty($reportData)) {
                $contacts = [];
                foreach ($reportData as $row) {
                    if (empty($row)) {
                        continue;
                    }

                    list($reason, $email) = explode(',', $row);
                    if (in_array($reason, $this->reasons)) {
                        $contacts[] = $email;
                    }
                }

                // get a time period for the last contact sync
                $cronMinutes = filter_var(
                    $this->helper->getConfigValue(Config::XML_PATH_CRON_SCHEDULE_CONTACT),
                    FILTER_SANITIZE_NUMBER_INT
                );
                $lastSyncPeriod = new \DateTime($this->dateTime->formatDate(true), new \DateTimeZone('UTC'));
                $lastSyncPeriod->sub(new \DateInterval("PT{$cronMinutes}M"));

                // check whether any last subscribe dates fall within the last subscriber sync period
                $recentlyResubscribed = array_filter(
                    $this->helper->contactResource->getLastSubscribedAtDates($contacts),
                    function ($contact) use ($lastSyncPeriod) {
                        $lastSubscribed = new \DateTime($contact['last_subscribed_at'], new \DateTimeZone('UTC'));
                        return $lastSubscribed >= $lastSyncPeriod;
                    }
                );

                if (!empty($recentlyResubscribed)) {
                    $importerModel = $this->importerFactory->create();
                    // queue resubscription jobs
                    foreach ($recentlyResubscribed as $resubscriber) {
                        $importerModel->registerQueue(
                            ImporterModel::IMPORT_TYPE_SUBSCRIBER_RESUBSCRIBED,
                            ['email' => $resubscriber['email']],
                            ImporterModel::MODE_SUBSCRIBER_RESUBSCRIBED,
                            $websiteId
                        );
                    }

                    // remove from unsubscribable emails
                    $contacts = array_diff($contacts, array_column($recentlyResubscribed, 'email'));
                }

                //unsubscribe from email contact and newsletter subscriber tables
                if (!empty($contacts)) {
                    $this->helper->contactResource->unsubscribe($contacts);
                }
            }
        }
    }

    /**
     * Convert utf8 data.
     *
     * @param string $text
     *
     * @return string
     */
    private function _removeUtf8Bom($text)
    {
        $bom = pack('H*', 'EFBBBF');
        $text = preg_replace("/^$bom/", '', $text);

        return $text;
    }

    /**
     * @return Client
     */
    private function getClient()
    {
        return $this->_getData('client');
    }
}
