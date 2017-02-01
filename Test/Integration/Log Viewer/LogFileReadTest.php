<?php

namespace Dotdigitalgroup\Email\Helper;

class LogFileReadTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    public $pathLogfile;
    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    public $helper;

    public function setup()
    {
        /** @var \Dotdigitalgroup\Email\Helper\File $helper */
        $this->helper = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->get('Dotdigitalgroup\Email\Helper\File');

        $filename = 'connector';
        $this->pathLogfile = $this->helper->directoryList->getPath('var') . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR
            . $filename . '.log';
    }

    public function test_empty_log_file_returns_no_error()
    {
        /**
         * Create empty file.
         */
        //@codingStandardsIgnoreStart
        $handle = fopen($this->pathLogfile, 'w');
        fwrite($handle, '');
        fclose($handle);
        //@codingStandardsIgnoreEnd

        $content = $this->helper->getLogFileContent('connector');

        $this->assertNotContains('Log file is not readable or does not exist at this moment', $content);
    }

    public function test_log_file_with_data_returns_no_error()
    {
        /**
         * Create empty file.
         */
        //@codingStandardsIgnoreStart
        $handle = fopen($this->pathLogfile, 'w');
        fwrite($handle, 'SOME TEXT DATA');
        fclose($handle);
        //@codingStandardsIgnoreEnd

        $content = $this->helper->getLogFileContent('connector');

        $this->assertNotContains('Log file is not readable or does not exist at this moment', $content);
    }

    public function test_log_file_not_exit_returns_error()
    {
        /**
         * Remove file
         */
        //@codingStandardsIgnoreStart
        unlink($this->pathLogfile);
        //@codingStandardsIgnoreEnd
        $content = $this->helper->getLogFileContent('connector');
        $this->assertContains('failed to open stream: No such file ', $content);
    }
}