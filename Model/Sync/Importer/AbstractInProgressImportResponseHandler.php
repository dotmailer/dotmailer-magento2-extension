<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Apiconnector\ClientInterface;
use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer as ImporterResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection as ImporterCollection;

abstract class AbstractInProgressImportResponseHandler
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ImporterResource
     */
    protected $importerResource;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected static $importStatuses = [
        'RejectedByWatchdog',
        'InvalidFileFormat',
        'Unknown',
        'Failed',
        'ExceedsAllowedContactLimit',
        'NotAvailableInThisVersion',
    ];

    /**
     * @param Logger $logger
     * @param ImporterResource $importerResource
     */
    public function __construct(
        Logger $logger,
        ImporterResource $importerResource
    ) {
        $this->logger = $logger;
        $this->importerResource = $importerResource;
    }

    /**
     * Process.
     *
     * @param array $group
     * @param ImporterCollection $items
     * @return int|void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(array $group, ImporterCollection $items)
    {
        $itemsCount = 0;

        foreach ($items as $item) {
            try {
                $response = $this->checkItemImportStatus($item, $group);
            } catch (\Exception $e) {
                $item->setMessage($e->getMessage())
                    ->setImportStatus(ImporterModel::FAILED);
                $this->importerResource->save($item);
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
     * @param array $group
     *
     * @return object|null
     */
    abstract protected function checkItemImportStatus(ImporterModel $item, array $group);

    /**
     * Process Response.
     *
     * @param Object $response
     * @param ImporterModel $item
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    abstract protected function processResponse($response, $item);

    /**
     * Process finished item.
     *
     * @param ImporterModel $item
     *
     * @return ImporterModel
     */
    protected function processFinishedItem(ImporterModel $item)
    {
        $now = gmdate('Y-m-d H:i:s');

        $item->setImportStatus(ImporterModel::IMPORTED)
            ->setImportFinished($now)
            ->setMessage('');

        return $item;
    }

    /**
     * Get the client.
     *
     * @param int $websiteId
     *
     * @return ClientInterface
     */
    abstract protected function getClient(int $websiteId);
}
