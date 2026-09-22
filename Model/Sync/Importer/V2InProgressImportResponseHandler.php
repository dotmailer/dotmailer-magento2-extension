<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Api\Model\Sync\Importer\InProgressImportResponseHandlerInterface;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;
use Dotdigitalgroup\Email\Model\Sync\Importer\Context\InProgressImportContext;
use Dotdigitalgroup\Email\Model\Sync\Importer\ReportHandler\V2ImporterReportHandler;

class V2InProgressImportResponseHandler implements InProgressImportResponseHandlerInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ImporterItemStatusManager
     */
    private $importerItemStatusManager;

    /**
     * @var V2ImporterReportHandler
     */
    private $reportHandler;

    /**
     * @var File
     */
    private $fileHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Data $helper
     * @param ImporterItemStatusManager $importerItemStatusManager
     * @param V2ImporterReportHandler $reportHandler
     * @param File $fileHelper
     * @param Logger $logger
     */
    public function __construct(
        Data $helper,
        ImporterItemStatusManager $importerItemStatusManager,
        V2ImporterReportHandler $reportHandler,
        File $fileHelper,
        Logger $logger
    ) {
        $this->helper = $helper;
        $this->importerItemStatusManager = $importerItemStatusManager;
        $this->reportHandler = $reportHandler;
        $this->fileHelper = $fileHelper;
        $this->logger = $logger;
    }

    /**
     * Process.
     *
     * @param InProgressImportContext $context
     * @param ImporterCollection $items
     * @return int
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(InProgressImportContext $context, ImporterCollection $items): int
    {
        $itemsCount = 0;

        foreach ($items as $item) {
            try {
                $response = $this->checkItemImportStatus($item, $context);
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf('Checking import id %s: %s', $item->getImportId(), $e->getMessage())
                );
                $this->importerItemStatusManager->markFailed($item, $e->getMessage());
                $this->importerItemStatusManager->save($item);
                continue;
            }

            $itemsCount += $this->processResponse($response, $item);
        }

        return $itemsCount;
    }

    /**
     * Check item import status.
     *
     * @param ImporterModel $item
     * @param InProgressImportContext $context
     *
     * @return object|null
     */
    private function checkItemImportStatus(
        ImporterModel $item,
        InProgressImportContext $context
    ) {
        $method = $context->getMethod();
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
    private function processResponse($response, $item)
    {
        $itemCount = 0;
        if (isset($response->message)) {
            $this->importerItemStatusManager->markFailed($item, $response->message);
        } elseif (isset($response->status)) {
            if ($response->status == 'Finished') {
                $item = $this->processFinishedItem($item);
            } elseif ($this->importerItemStatusManager->isFailedStatus($response->status)) {
                $this->importerItemStatusManager->markFailed(
                    $item,
                    'Import failed with status ' . $response->status
                );
            } else {
                //Not finished
                $itemCount = 1;
            }
        }

        $this->importerItemStatusManager->save($item);

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
    private function processFinishedItem(ImporterModel $item)
    {
        $item = $this->importerItemStatusManager->markImported($item);

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
     * Get the V2 client for a website.
     *
     * @param int|string $websiteId
     *
     * @return Client
     */
    private function getClient($websiteId)
    {
        return $this->helper->getWebsiteApiClient((int) $websiteId);
    }
}
