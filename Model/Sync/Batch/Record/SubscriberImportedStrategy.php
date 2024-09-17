<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Record;

use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Record\RecordImportedStrategyInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory as ContactResourceFactory;

class SubscriberImportedStrategy implements RecordImportedStrategyInterface
{
    /**
     * @var array
     */
    private $records = [];

    /**
     * @var ContactResourceFactory
     */
    private $contactResourceFactory;

    /**
     * @param ContactResourceFactory $contactResourceFactory
     */
    public function __construct(
        ContactResourceFactory $contactResourceFactory
    ) {
        $this->contactResourceFactory = $contactResourceFactory;
    }

    /**
     * @inheritDoc
     */
    public function setRecords(array $records): SubscriberImportedStrategy
    {
        $this->records = $records;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function process(): void
    {
        $this->contactResourceFactory->create()
            ->setSubscribersImportedByIds(
                array_keys($this->records)
            );
    }
}
