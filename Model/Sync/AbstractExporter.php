<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Model\Sync;

use Dotdigital\V3\Models\Contact as SdkContact;
use Dotdigitalgroup\Email\Model\Sync\Export\CsvHandler;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Api\Data\WebsiteInterface;

/**
 * @deprecated We will be removing this class in favour of using composition instead of inheritance.
 * @see \Dotdigitalgroup\Email\Model\Sync\Customer\Exporter
 */
abstract class AbstractExporter
{
    /**
     * @var array
     */
    public const EMAIL_FIELDS = [
        'email' => 'Email',
        'email_type' => 'EmailType',
    ];

    /**
     * @var CsvHandler
     */
    private $csvHandler;

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @param CsvHandler $csvHandler
     */
    public function __construct(
        CsvHandler $csvHandler
    ) {
        $this->csvHandler = $csvHandler;
    }

    /**
     * Export.
     *
     * @param array $contacts
     * @param WebsiteInterface $website
     * @param int $listId
     *
     * @return array<SdkContact>
     */
    abstract public function export(array $contacts, WebsiteInterface $website, int $listId);

    /**
     * Set CSV columns for export.
     *
     * @param WebsiteInterface $website
     *
     * @return void
     *
     * @deprecated We no longer send data using csv files.
     * @see \Dotdigitalgroup\Email\Model\Sync\Customer\Exporter::setFieldMapping (for example)
     */
    abstract public function setCsvColumns(WebsiteInterface $website);

    /**
     * Get CSV columns.
     *
     * @return array
     *
     * @deprecated We no longer send data using csv files.
     * @see \Dotdigitalgroup\Email\Model\Sync\Customer\Exporter (for example)
     */
    public function getCsvColumns()
    {
        return $this->columns;
    }

    /**
     * Set additional data.
     *
     * @param AbstractModel $model
     * @param array $data
     */
    protected function setAdditionalDataOnModel(AbstractModel $model, array $data)
    {
        foreach ($data as $column => $value) {
            $model->setData($column, $value);
        }
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
     *
     * @deprecated CSV data transfer is replaced with JSON.
     * @see \Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessor
     */
    public function initialiseCsvFile(
        WebsiteInterface $website,
        array $columns,
        string $syncType,
        string $filename = ''
    ) {
        return $this->csvHandler->initialiseCsvFile($website, $columns, $syncType, $filename);
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
     *
     * @deprecated CSV data transfer is replaced with JSON.
     * @see \Dotdigitalgroup\Email\Model\Sync\Batch\MegaBatchProcessor
     */
    public function getCsvFileName($websiteCode, $syncType)
    {
        return $this->csvHandler->getCsvFileName($websiteCode, $syncType);
    }
}
