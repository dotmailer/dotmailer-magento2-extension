<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigital\V3\Models\Import\ImportInterface as V3ImportInterface;
use Dotdigitalgroup\Email\Api\Model\Sync\Importer\InProgressImportResponseHandlerInterface;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;
use Dotdigitalgroup\Email\Model\Sync\Importer\Context\InProgressImportContext;
use Dotdigitalgroup\Email\Model\Sync\Importer\ReportHandler\V3ImporterReportHandler;

class V3InProgressImportResponseHandler implements InProgressImportResponseHandlerInterface
{
    /**
     * @var V3ImportStatusChecker
     */
    private $importStatusChecker;

    /**
     * @var ImporterItemStatusManager
     */
    private $importerItemStatusManager;

    /**
     * @var V3ImporterReportHandler
     */
    private $reportHandler;

    /**
     * @param V3ImportStatusChecker $importStatusChecker
     * @param ImporterItemStatusManager $importerItemStatusManager
     * @param V3ImporterReportHandler $reportHandler
     */
    public function __construct(
        V3ImportStatusChecker $importStatusChecker,
        ImporterItemStatusManager $importerItemStatusManager,
        V3ImporterReportHandler $reportHandler
    ) {
        $this->importStatusChecker = $importStatusChecker;
        $this->importerItemStatusManager = $importerItemStatusManager;
        $this->reportHandler = $reportHandler;
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
                $response = $this->importStatusChecker->check($item, $context);
            } catch (\Exception $e) {
                $this->importerItemStatusManager->markFailed($item, $e->getMessage());
                $this->importerItemStatusManager->save($item);
                continue;
            }

            $itemsCount += $this->processResponse($response, $item);
        }

        return $itemsCount;
    }

    /**
     * Process Response.
     *
     * @param V3ImportInterface $response
     * @param ImporterModel $item
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function processResponse($response, $item)
    {
        $itemCount = 0;
        if ($response->getStatus() == 'Finished') {
            $item = $this->importerItemStatusManager->markImported($item);
        } elseif ($this->importerItemStatusManager->isFailedStatus($response->getStatus())) {
            $this->importerItemStatusManager->markFailed(
                $item,
                'Import failed with status ' . $response->getStatus()
            );
        } else {
            //Not finished
            $itemCount = 1;
        }

        $this->reportHandler->logSummary($response);
        $this->reportHandler->logFailures($response);
        $this->reportHandler->storeContactIds($response, (int) $item->getWebsiteId());

        $this->importerItemStatusManager->save($item);

        return $itemCount;
    }
}
