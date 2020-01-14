<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Magento\Framework\Filesystem\DriverInterface;

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
     * @var DriverInterface
     */
    private $driver;

    /**
     * ContactImportQueueExport constructor.
     *
     * @param \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory
     * @param \Dotdigitalgroup\Email\Helper\File $file
     * @param DriverInterface $driver
     */
    public function __construct(
        \Dotdigitalgroup\Email\Model\ImporterFactory $importerFactory,
        \Dotdigitalgroup\Email\Helper\File $file,
        DriverInterface $driver
    ) {
        $this->importerFactory = $importerFactory;
        $this->file = $file;
        $this->driver = $driver;
    }

    /**
     * @param \Magento\Store\Api\Data\WebsiteInterface $website
     * @param string $customersFile
     * @param int $customerNum
     * @param array $customerIds
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Contact $resource
     *
     * @return null
     */
    public function enqueueForExport(
        \Magento\Store\Api\Data\WebsiteInterface $website,
        $customersFile,
        $customerNum,
        $customerIds,
        $resource
    ) {
        if ($this->driver->isFile($this->file->getFilePath($customersFile))) {
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
                $resource->setImportedByIds($customerIds);
            }
        }
    }
}
