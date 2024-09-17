<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Record;

use Dotdigitalgroup\Email\Api\Model\Sync\Batch\Record\RecordImportedStrategyInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory as ContactResourceFactory;

/**
 * Class ContactImportedStrategy
 *
 * This class implements the RecordImportedStrategyInterface and provides methods to set record IDs
 * and process the imported contacts.
 */
class ContactImportedStrategy implements RecordImportedStrategyInterface
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
     * ContactImportedStrategy constructor.
     *
     * @param ContactResourceFactory $contactResourceFactory
     */
    public function __construct(
        ContactResourceFactory $contactResourceFactory
    ) {
        $this->contactResourceFactory = $contactResourceFactory;
    }

    /**
     * Set the records for the batch.
     *
     * @param array $records
     * @return ContactImportedStrategy
     */
    public function setRecords(array $records): ContactImportedStrategy
    {
        $this->records = $records;
        return $this;
    }

    /**
     * Process the imported contacts.
     *
     * @return void
     */
    public function process(): void
    {
        $this->contactResourceFactory->create()
            ->setContactsImportedByIds(
                array_keys($this->records)
            );
    }
}
