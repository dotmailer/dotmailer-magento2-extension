<?php

namespace Dotdigitalgroup\Email\Helper;

/**
 * Creates the csv files in export folder and move to archive when it's complete.
 * Log info and debug to a custom log file connector.log
 */
class File
{
    /**
     * @var string
     */
    private $outputFolder;

    /**
     * @var string
     */
    private $outputArchiveFolder;

    /**
     * @var string
     */
    private $delimiter;

    /**
     * @var string
     */
    private $enclosure;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Consent
     */
    private $consentResource;

    /**
     * @var \Magento\Framework\File\Csv
     */
    private $csv;

    /**
     * File constructor.
     *
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Dotdigitalgroup\Email\Model\ResourceModel\Consent $consentResource
     * @param \Magento\Framework\File\Csv $csv
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Dotdigitalgroup\Email\Model\ResourceModel\Consent $consentResource,
        \Magento\Framework\File\Csv $csv
    ) {
        $this->csv = $csv;
        $this->consentResource = $consentResource;
        $this->directoryList       = $directoryList;
        $varPath                   = $directoryList->getPath('var');
        $this->outputFolder        = $varPath . DIRECTORY_SEPARATOR . 'export' . DIRECTORY_SEPARATOR . 'email';
        $this->outputArchiveFolder = $this->outputFolder . DIRECTORY_SEPARATOR . 'archive';
        // tab character
        $this->delimiter = ',';
        $this->enclosure = '"';
    }

    /**
     * @return string
     */
    private function getOutputFolder()
    {
        $this->createDirectoryIfNotExists($this->outputFolder);

        return $this->outputFolder;
    }

    /**
     * @return string
     */
    public function getArchiveFolder()
    {
        $this->createDirectoryIfNotExists($this->outputArchiveFolder);

        return $this->outputArchiveFolder;
    }

    /**
     *  Return the full filepath.
     *
     * @param string $filename
     *
     * @return string
     */
    public function getFilePath($filename)
    {
        return $this->getOutputFolder() . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Move file to archive dir.
     *
     * @param string $filename
     *
     * @return null
     */
    public function archiveCSV($filename)
    {
        $this->moveFile(
            $this->getOutputFolder(),
            $this->getArchiveFolder(),
            $filename
        );
    }

    /**
     * Moves the output file from one folder to the next.
     *
     * @param string $sourceFolder
     * @param string $destFolder
     * @param string $filename
     *
     * @return null
     */
    private function moveFile($sourceFolder, $destFolder, $filename)
    {
        // generate the full file paths
        $sourceFilepath = $sourceFolder . DIRECTORY_SEPARATOR . $filename;
        $destFilepath = $destFolder . DIRECTORY_SEPARATOR . $filename;

        // rename the file
        rename($sourceFilepath, $destFilepath);
    }

    /**
     * Output an array to the output file FORCING Quotes around all fields.
     *
     * @param string $filepath
     * @param mixed $csv
     *
     * @throws \Exception
     *
     * @return null
     */
    public function outputForceQuotesCSV($filepath, $csv)
    {
        $fqCsv = $this->arrayToCsv($csv, chr(9), '"', true, false);
        // Open for writing only; place the file pointer at the end of the file.
        // If the file does not exist, attempt to create it.
        $fp = fopen($filepath, 'a');

        // for some reason passing the preset delimiter/enclosure variables results in error
        // $this->delimiter $this->enclosure
        if (fwrite($fp, $fqCsv) == 0) {
            throw new \Exception('Problem writing CSV file');
        }
        fclose($fp);
    }

    /**
     * @param string $filepath
     * @param array $csv
     *
     * @return null
     */
    public function outputCSV($filepath, $csv)
    {
        /*
         * Open for writing only; place the file pointer at the end of the file.
         * If the file does not exist, attempt to create it.
         */
        $handle = fopen($filepath, 'a');
        fputcsv($handle, $csv, ',', '"');
        fclose($handle);
    }

    /**
     * If the path does not exist then create it.
     *
     * @param string $path
     *
     * @return null
     */
    private function createDirectoryIfNotExists($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0750, true);
        }
    }

    /**
     * Convert array into the csv.
     *
     * @param array $fields
     * @param string $delimiter
     * @param string $enclosure
     * @param bool $encloseAll
     * @param bool $nullToMysqlNull
     *
     * @return string
     */
    private function arrayToCsv(
        array &$fields,
        $delimiter,
        $enclosure,
        $encloseAll = false,
        $nullToMysqlNull = false
    ) {
        $delimiterEsc = preg_quote($delimiter, '/');
        $enclosureEsc = preg_quote($enclosure, '/');

        $output = [];
        foreach ($fields as $field) {
            if ($field === null && $nullToMysqlNull) {
                $output[] = 'NULL';
                continue;
            }

            // Enclose fields containing $delimiter, $enclosure or whitespace
            if ($encloseAll
                || preg_match(
                    "/(?:$delimiterEsc|$enclosureEsc|\s)/",
                    $field
                )
            ) {
                $output[] = $enclosure . str_replace(
                    $enclosure,
                    $enclosure . $enclosure,
                    $field
                ) . $enclosure;
            } else {
                $output[] = $field;
            }
        }

        return implode($delimiter, $output) . "\n";
    }

    /**
     * Delete file or directory.
     *
     * @param string $path
     *
     * @return bool
     */
    public function deleteDir($path)
    {
        if (strpos($path, $this->directoryList->getPath('var')) === false) {
            return sprintf("Failed to delete directory - '%s'", $path);
        }

        $classFunc = [__CLASS__, __FUNCTION__];
        return is_file($path)
            ?
            @unlink($path)
            :
            array_map($classFunc, glob($path . '/*')) == @rmdir($path);
    }

    /**
     * Get log file content.
     *
     * @param string $filename
     *
     * @return string
     */
    public function getLogFileContent($filename = 'connector')
    {
        switch ($filename) {
            case "connector":
                $filename = 'connector.log';
                break;
            case "system":
                $filename = 'system.log';
                break;
            case "exception":
                $filename = 'exception.log';
                break;
            case "debug":
                $filename = 'debug.log';
                break;
            default:
                return "Log file is not valid. Log file name is " . $filename;
        }
        $pathLogfile = $this->directoryList->getPath('log') . DIRECTORY_SEPARATOR . $filename;
        //tail the length file content
        $lengthBefore = 500000;
        try {
            $contents = '';
            $handle = fopen($pathLogfile, 'r');
            fseek($handle, -$lengthBefore, SEEK_END);
            if (!$handle) {
                return "Log file is not readable or does not exist at this moment. File path is "
                . $pathLogfile;
            }

            if (filesize($pathLogfile) > 0) {
                $contents = fread($handle, filesize($pathLogfile));
                if ($contents === false) {
                    return "Log file is not readable or does not exist at this moment. File path is "
                        . $pathLogfile;
                }
                fclose($handle);
            }
            return $contents;
        } catch (\Exception $e) {
            return $e->getMessage() . $pathLogfile;
        }
    }

    /**
     * @param string $file full path to the csv file.
     * @return bool|string
     */
    public function cleanProcessedConsent($file)
    {
        //read file and get the email addresses
        $index = $this->csv->getDataPairs($file, 0, 0);
        //remove header data for Email
        unset($index['Email']);
        $emails = array_values($index);
        $log = false;

        try {
            $result = $this->consentResource
                ->deleteConsentByEmails($emails);
            if ($count = count($result)) {
                $log = 'Consent data removed : ' . $count;
            }
        } catch (\Exception $e) {
            $log = $e->getMessage();
        }

        return $log;
    }

    /**
     * Return the full file path with checking in archive as fallback.
     *
     * @param string $filename
     * @return string
     */
    public function getFilePathWithFallback($filename)
    {
        $emailPath = $this->getOutputFolder() . DIRECTORY_SEPARATOR . $filename;
        $archivePath = $this->getArchiveFolder() . DIRECTORY_SEPARATOR . $filename;
        return is_file($emailPath) ? $emailPath : $archivePath;
    }

    /**
     * Check if file exists in email or archive folder
     *
     * @param string $filename
     * @return boolean
     */
    public function isFilePathExistWithFallback($filename)
    {
        $emailPath = $this->getOutputFolder() . DIRECTORY_SEPARATOR . $filename;
        $archivePath = $this->getArchiveFolder() . DIRECTORY_SEPARATOR . $filename;
        return is_file($emailPath) ? true : (is_file($archivePath) ? true : false);
    }

    /**
     * @param string $filename
     * @return bool
     */
    public function isFileAlreadyArchived($filename)
    {
       return is_file($this->getArchiveFolder() . DIRECTORY_SEPARATOR . $filename);
    }
}
