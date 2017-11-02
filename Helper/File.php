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
     * @var string
     */
    private $logFileName = 'connector.log';
    
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * File constructor.
     *
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     */
    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
        $this->directoryList       = $directoryList;
        $varPath                   = $directoryList->getPath('var');
        $this->outputFolder        = $varPath . DIRECTORY_SEPARATOR . 'export' . DIRECTORY_SEPARATOR . 'email';
        $this->outputArchiveFolder = $this->outputFolder . DIRECTORY_SEPARATOR . 'archive';
        // tab character
        $this->delimiter = ',';
        $this->enclosure = '"';

        $logDir = $directoryList->getPath('log');
        if (! is_dir($logDir)) {
            mkdir($directoryList->getPath('var')  . DIRECTORY_SEPARATOR . 'log');
        }
        $writer = new \Zend\Log\Writer\Stream($logDir . DIRECTORY_SEPARATOR .  $this->logFileName);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $this->connectorLogger  = $logger;
    }

    /**
     * @return string
     */
    private function getOutputFolder()
    {
        $this->pathExists($this->outputFolder);

        return $this->outputFolder;
    }

    /**
     * @return string
     */
    public function getArchiveFolder()
    {
        $this->pathExists($this->outputArchiveFolder);

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
     * @param mixed $filename
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
     * @param mixed $sourceFolder
     * @param mixed $destFolder
     * @param mixed $filename
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
     * @param mixed $filepath
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
     * @param mixed $filepath
     * @param mixed $csv
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
     * @param mixed $path
     *
     * @return null
     */
    private function pathExists($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 750, true);
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
     * @param mixed $path
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
     * @param mixed $data
     *
     * @return null
     */
    public function info($data)
    {
        $this->connectorLogger->info($data);
    }

    /**
     * @param mixed $message
     * @param mixed $extra
     *
     * @return null
     */
    public function debug($message, $extra)
    {
        $this->connectorLogger->debug($message, $extra);
    }
}
