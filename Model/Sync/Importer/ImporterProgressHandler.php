<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Magento\Framework\DataObject;

class ImporterProgressHandler extends DataObject
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var File
     */
    private $fileHelper;

    /**
     * @var ImporterFactory
     */
    private $importerFactory;

    /**
     * @var ImporterReportHandler
     */
    private $reportHandler;

    /**
     * @var Client
     */
    private $client;

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
     * ImporterProgressHandler constructor.
     *
     * @param Logger $logger
     * @param File $fileHelper
     * @param ImporterFactory $importerFactory
     * @param ImporterReportHandler $reportHandler
     * @param array $data
     */
    public function __construct(
        Logger $logger,
        File $fileHelper,
        ImporterFactory $importerFactory,
        ImporterReportHandler $reportHandler,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->fileHelper = $fileHelper;
        $this->importerFactory = $importerFactory;
        $this->reportHandler = $reportHandler;

        parent::__construct($data);
    }

    /**
     * Check imports in progress for an array of website ids.
     *
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
                    $item->getImportType() == ImporterModel::IMPORT_TYPE_CUSTOMER ||
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
     * Process Response.
     *
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
        } elseif (isset($response->status)) {
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
     * Process finished item.
     *
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

        switch ($item->getImportType()) {
            case ImporterModel::IMPORT_TYPE_CONTACT:
            case ImporterModel::IMPORT_TYPE_CUSTOMER:
            case ImporterModel::IMPORT_TYPE_SUBSCRIBERS:
            case ImporterModel::IMPORT_TYPE_GUEST:
                $this->processContactFinishedItems($item);
                break;
            case ImporterModel::IMPORT_TYPE_ORDERS:
            case ImporterModel::IMPORT_TYPE_REVIEWS:
            case ImporterModel::IMPORT_TYPE_WISHLIST:
                $this->processInsightDataItems($item);
                break;
        }

        return $item;
    }

    /**
     * Process contact import items.
     *
     * @param ImporterModel $item
     * @return void
     */
    private function processContactFinishedItems($item)
    {
        $file = $item->getImportFile();
        // if a filename is stored in the table and if that file physically exists
        if ($file && $this->fileHelper->isFilePathExistWithFallback($file)) {
            //remove the consent data for contacts before archiving the file
            $log = $this->fileHelper->cleanProcessedConsent(
                $this->fileHelper->getFilePathWithFallback($file)
            );
            if ($log) {
                $this->logger->info($log);
            }
            if (! $this->fileHelper->isFileAlreadyArchived($file)) {
                $this->fileHelper->archiveCSV($file);
            }
        }

        if ($item->getImportId()) {
            $this->reportHandler->processContactImportReportFaults(
                $item->getImportId(),
                $item->getWebsiteId(),
                $item->getImportType(),
                $this->client
            );
        }
    }

    /**
     * Process insight data items.
     *
     * @param ImporterModel $item
     * @return void
     */
    private function processInsightDataItems($item)
    {
        if ($item->getImportId()) {
            $this->reportHandler->processInsightReportFaults(
                $item->getImportId(),
                $item->getWebsiteId(),
                $this->client,
                $item->getImportType()
            );
        }
    }

    /**
     * Fetch client object.
     *
     * @return Client
     */
    private function getClient()
    {
        return $this->_getData('client');
    }
}
