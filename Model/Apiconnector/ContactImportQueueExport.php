<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

/**
 * Class ContactImportQueueExport
 * @package Dotdigitalgroup\Email\Model\Apiconnector
 */
class ContactImportQueueExport
{
    /**
     * @var \Dotdigitalgroup\Email\Model\ImporterFactory
     */
    public $importerFactory;
    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    public $file;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    public $resource;

    /**
     * ContactImportQueueExport constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Magento\Framework\App\ResourceConnection                        $resource
     * @param \Dotdigitalgroup\Email\Helper\File                               $file
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Dotdigitalgroup\Email\Helper\File $file
    ) {
        $this->importerFactory = $importerFactory;
        $this->file            = $file;
        $this->resource        = $resource;
    }

    /**
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param $customersFile
     * @param $customerNum
     * @param $customerIds
     *
     * @param $connection
     */
    public function enqueueForExport(
        \Magento\Store\Api\Data\WebsiteInterface $website,
        $customersFile,
        $customerNum,
        $customerIds,
        $connection
    ) {
        //@codingStandardsIgnoreStart
        if (is_file($this->file->getFilePath($customersFile))) {
            //@codingStandardsIgnoreEnd
            if ($customerNum > 0) {
                //register in queue with importer
                $this->importerFactory->create()
                    ->registerQueue(
                        \Dotdigitalgroup\Email\Model\Importer::IMPORT_TYPE_CONTACT,
                        '',
                        \Dotdigitalgroup\Email\Model\Importer::MODE_BULK,
                        $website->getId(),
                        $customersFile
                    );
                //set imported

                $tableName = $this->resource->getTableName('email_contact');
                $ids = implode(', ', $customerIds);
                $connection->update(
                    $tableName,
                    ['email_imported' => 1],
                    ["customer_id IN (?)" => $ids]
                );
            }
        }
    }
}
