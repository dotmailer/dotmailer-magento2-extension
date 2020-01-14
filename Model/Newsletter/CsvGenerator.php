<?php

namespace Dotdigitalgroup\Email\Model\Newsletter;

use Dotdigitalgroup\Email\Helper\File;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Api\Data\StoreInterface;

class CsvGenerator
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var boolean
     */
    private $optInType;

    /**
     * CsvGenerator constructor.
     *
     * @param File $file
     */
    public function __construct(
        File $file
    ) {
        $this->file = $file;
    }

    /**
     * @param StoreInterface $store
     * @return boolean
     */
    public function isOptInTypeDouble($store)
    {
        if (! isset($this->optInType)) {
            $this->optInType = (boolean)$store->getConfig(Subscriber::XML_PATH_CONFIRMATION_FLAG);
        }
        return $this->optInType;
    }

    /**
     * @param StoreInterface $store
     * @param string $storeName
     * @param string $websiteName
     * @return $this
     */
    public function createHeaders($store, $storeName = '', $websiteName = '')
    {
        $this->headers = ['Email', 'EmailType'];
        if (strlen($storeName)) {
            $this->headers[] = $storeName;
        }
        if (strlen($websiteName)) {
            $this->headers[] = $websiteName;
        }
        $optInType = $this->isOptInTypeDouble($store);
        if ($optInType) {
            $this->headers[] = 'OptInType';
        }
        return $this;
    }

    /**
     * @param array $mappedHash
     * @return $this
     */
    public function mergeHeaders($mappedHash)
    {
        $this->headers = array_merge($this->headers, array_values($mappedHash));
        return $this;
    }

    /**
     * @return $this
     */
    public function outputHeadersToFile()
    {
        $this->file->outputCSV($this->filePath, $this->headers);
        return $this;
    }

    /**
     * @param array $outputData
     * @return $this
     */
    public function outputDataToFile(array $outputData)
    {
        $this->file->outputCSV($this->filePath, $outputData);
        return $this;
    }

    /**
     * @param string $subscribersFile
     * @return $this
     */
    public function createCsv($subscribersFile)
    {
        $this->filePath = $this->file->getFilePath($subscribersFile);
        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $subscribersFile
     * @return string
     */
    public function getFilePath($subscribersFile)
    {
        return $this->file->getFilePath($subscribersFile);
    }
}
