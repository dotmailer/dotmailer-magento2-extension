<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer as ImporterResource;
use Dotdigitalgroup\Email\Model\Sync\Importer\ReportHandler\V2ImporterReportHandler;

class V2InProgressImportResponseHandler extends AbstractInProgressImportResponseHandler
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var V2ImporterReportHandler
     */
    private $reportHandler;

    /**
     * @var File
     */
    private $fileHelper;

    /**
     * @param Data $helper
     * @param ImporterResource $importerResource
     * @param V2ImporterReportHandler $reportHandler
     * @param File $fileHelper
     * @param Logger $logger
     */
    public function __construct(
        Data $helper,
        ImporterResource $importerResource,
        V2ImporterReportHandler $reportHandler,
        File $fileHelper,
        Logger $logger
    ) {
        $this->helper = $helper;
        $this->reportHandler = $reportHandler;
        $this->fileHelper = $fileHelper;
        parent::__construct($logger, $importerResource);
    }

    /**
     * Check item import status.
     *
     * @param ImporterModel $item
     * @param array $group
     *
     * @return object|null
     */
    protected function checkItemImportStatus(
        ImporterModel $item,
        array $group
    ) {
        $method = $group['method'];
        return $this->getClient($item->getWebsiteId())
            ->$method($item->getImportId());
    }

    /**
     * Process Response.
     *
     * @param Object $response
     * @param ImporterModel $item
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function processResponse($response, $item)
    {
        $itemCount = 0;
        if (isset($response->message)) {
            $item->setImportStatus(ImporterModel::FAILED)
                ->setMessage($response->message);
        } elseif (isset($response->status)) {
            if ($response->status == 'Finished') {
                $item = $this->processFinishedItem($item);
            } elseif (in_array($response->status, self::$importStatuses)) {
                $item->setImportStatus(ImporterModel::FAILED)
                    ->setMessage('Import failed with status ' . $response->status);
            } else {
                //Not finished
                $itemCount = 1;
            }
        }

        $this->importerResource->save($item);

        return $itemCount;
    }

    /**
     * Process finished item.
     *
     * @param ImporterModel $item
     *
     * @return ImporterModel
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    protected function processFinishedItem(ImporterModel $item)
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
                $this->processContactFinishedItem($item);
                break;
            case ImporterModel::IMPORT_TYPE_ORDERS:
            case ImporterModel::IMPORT_TYPE_REVIEWS:
            case ImporterModel::IMPORT_TYPE_WISHLIST:
                $this->processInsightDataItem($item);
                break;
        }

        return $item;
    }

    /**
     * Process contact import items.
     *
     * @param ImporterModel $item
     * @return void
     * @throws \Exception
     */
    private function processContactFinishedItem(ImporterModel $item)
    {
        $file = $item->getImportFile();
        // if a filename is stored in the table and if that file physically exists
        if ($file && $this->fileHelper->isFilePathExistWithFallback($file)) {
            if (! $this->fileHelper->isFileAlreadyArchived($file)) {
                $this->fileHelper->archiveCSV($file);
            }
        }

        if ($item->getImportId()) {
            $this->reportHandler->processContactImportReportFaults(
                $item->getImportId(),
                $item->getWebsiteId(),
                $item->getImportType(),
                $this->getClient($item->getWebsiteId())
            );
        }
    }

    /**
     * Process insight data item.
     *
     * @param ImporterModel $item
     * @return void
     */
    private function processInsightDataItem(ImporterModel $item)
    {
        if ($item->getImportId()) {
            $this->reportHandler->processInsightReportFaults(
                $item->getImportId(),
                $item->getWebsiteId(),
                $this->getClient($item->getWebsiteId()),
                $item->getImportType()
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function getClient($websiteId)
    {
        if (!isset($this->client)) {
            return $this->helper->getWebsiteApiClient($websiteId);
        }
        return $this->client;
    }
}
