<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync\Batch\Record;

use Dotdigitalgroup\Email\Model\ResourceModel\ContactFactory as ContactResourceFactory;
use Magento\Framework\Exception\LocalizedException;

class ContactImportedStrategy implements RecordImportedStrategyInterface
{
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
     * Mark as imported.
     *
     * @param array $ids
     *
     * @return void
     * @throws LocalizedException
     */
    public function markAsImported(array $ids): void
    {
        $this->contactResourceFactory->create()
            ->setContactsImportedByIds(
                $ids
            );
    }
}
