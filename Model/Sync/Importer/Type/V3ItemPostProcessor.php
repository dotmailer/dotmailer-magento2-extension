<?php

namespace Dotdigitalgroup\Email\Model\Sync\Importer\Type;

use Dotdigitalgroup\Email\Model\Importer as ImporterModel;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Stdlib\DateTime;

class V3ItemPostProcessor implements ItemPostProcessorInterface
{
    /**
     * @var Importer
     */
    private $importerResource;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param Importer $importerResource
     * @param DateTime $dateTime
     */
    public function __construct(
        Importer $importerResource,
        DateTime $dateTime
    ) {
        $this->importerResource = $importerResource;
        $this->dateTime = $dateTime;
    }

    /**
     * Handle item after sync.
     *
     * Retry count is incremented because if this code is running, it means the item is being retried.
     * Initial import for bulk items is handled in MegaBatchProcessor.
     *
     * @param ImporterModel $item
     * @param string $result
     *
     * @return void
     * @throws AlreadyExistsException|CouldNotSaveException
     */
    public function handleItemAfterSync($item, $result)
    : void
    {
        if (!$result) {
            $item->setImportStatus(ImporterModel::FAILED);
            $this->importerResource->save($item);
            return;
        }

        $previousRetryCount = $item->getRetryCount();

        $item->setImportStatus(ImporterModel::IMPORTING)
            ->setImportId($result)
            ->setImportStarted($this->dateTime->formatDate(true))
            ->setMessage('')
            ->setRetryCount(++$previousRetryCount);

        $this->importerResource->save($item);
    }
}
