<?php

namespace Dotdigitalgroup\Email\Model\Sync\Export;

use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Logger\Logger;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Store\Api\Data\WebsiteInterface;

class CsvHandler
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * CsvGenerator constructor.
     *
     * @param File $file
     * @param Logger $logger
     * @param DriverInterface $driver
     */
    public function __construct(
        File $file,
        Logger $logger,
        DriverInterface $driver
    ) {
        $this->file = $file;
        $this->logger = $logger;
        $this->driver = $driver;
    }

    /**
     * Create CSV file and return its name.
     *
     * @param WebsiteInterface $website
     * @param array $columns
     * @param string $syncType
     * @param string $filename
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function initialiseCsvFile(
        WebsiteInterface $website,
        array $columns,
        string $syncType,
        string $filename = ''
    ) {
        $filename = $filename ?: $this->getCsvFileName($website->getCode(), $syncType);
        $filepath = $this->file->getFilePath($filename);
        $this->outputColumnHeadingsToFile($filepath, $columns);

        if (!$this->file->isFile($filepath)) {
            throw new \Magento\Framework\Exception\FileSystemException(
                __('File %1 does not exist.', $filepath)
            );
        }

        $this->logger->info(
            sprintf(
                '----------- %s sync ----------- : Website %d',
                ucwords($syncType),
                $website->getId()
            )
        );

        return $filename;
    }

    /**
     * Set the file name for the CSV.
     *
     * Random bytes are appended to prevent reuse of an already-processed file.
     * This can happen when the sync runs very fast, or isn't handling much data
     * (e.g. small batch size).
     *
     * @param string $websiteCode
     * @param string $syncType
     *
     * @return string
     */
    public function getCsvFileName($websiteCode, $syncType)
    {
        return strtolower(
            sprintf(
                '%s_%s_%s_%s.csv',
                $websiteCode,
                $syncType,
                date('d_m_Y_His'),
                bin2hex(random_bytes(3))
            )
        );
    }

    /**
     * Write the headings row.
     *
     * @param string $filepath
     * @param array $columns
     *
     * @return void
     */
    private function outputColumnHeadingsToFile($filepath, $columns)
    {
        $handle = $this->driver->fileOpen($filepath, 'a');
        $this->driver->filePutCsv(
            $handle,
            $columns,
            ',',
            '"'
        );
        $this->driver->fileClose($handle);
    }
}
