<?php

namespace Dotdigitalgroup\Email\Helper;

use Magento\TestFramework\ObjectManager;

class LogFileReadTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    public $pathLogfile = '';
    /**
     * @var \Dotdigitalgroup\Email\Helper\File
     */
    public $fileHelper;
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    public $objectManager;
    /**
     * @var  \Dotdigitalgroup\Email\Helper\Data
     */
    public $helper;


    public function setup()
    {
        $this->objectManager = ObjectManager::getInstance();
        /** @var \Dotdigitalgroup\Email\Helper\File $helper */
        $this->fileHelper = $this->objectManager->get(\Dotdigitalgroup\Email\Helper\File::class);
        $this->helper = $this->objectManager->get(\Dotdigitalgroup\Email\Helper\Data::class);
    }

    public function test_file_exists_and_content_contains_message()
    {

        $this->helper->log('logged message data');

        $content = $this->fileHelper->getLogFileContent();

        $this->assertContains('logged message', $content);

    }

    public function test_debug_log_contains_data_message()
    {
        $this->helper->debug('Dummy Title', ['mesage dummy text']);

        $this->assertContains('dummy text', $this->fileHelper->getLogFileContent());
    }

    public function test_empty_log_file_returns_no_error()
    {
        $content = $this->fileHelper->getLogFileContent();
        $this->assertNotContains('Log file is not readable or does not exist at this moment', $content);
    }

    public function test_log_file_with_data_returns_no_error()
    {
        $this->helper->log('SOME TEXT DATA');

        $content = $this->fileHelper->getLogFileContent();

        $this->assertNotContains('Log file is not readable or does not exist at this moment', $content);
    }
    
}