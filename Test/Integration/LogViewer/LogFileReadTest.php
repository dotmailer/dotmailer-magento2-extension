<?php

namespace Dotdigitalgroup\Email\Helper;

use Magento\TestFramework\ObjectManager;

class LogFileReadTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @return void
     */
    public function setup()
    {
        $this->objectManager = ObjectManager::getInstance();
        /** @var \Dotdigitalgroup\Email\Helper\File $helper */
        $this->fileHelper = $this->objectManager->get(\Dotdigitalgroup\Email\Helper\File::class);
        $this->helper = $this->objectManager->get(\Dotdigitalgroup\Email\Helper\Data::class);
    }

    /**
     * @return void
     */
    public function testFileExistsAndContentContainsMessage()
    {

        $this->helper->log('logged message data');

        $content = $this->fileHelper->getLogFileContent();

        $this->assertContains('logged message', $content);
    }

    /**
     * @return void
     */
    public function testDebugLogContainsDataMessage()
    {
        $this->helper->debug('Dummy Title', ['mesage dummy text']);

        $this->assertContains('dummy text', $this->fileHelper->getLogFileContent());
    }

    /**
     * @return void
     */
    public function testEmptyLogFileReturnsNoError()
    {
        $content = $this->fileHelper->getLogFileContent();
        $this->assertNotContains('Log file is not readable or does not exist at this moment', $content);
    }

    /**
     * @return void
     */
    public function testLogFileWithDataReturnsNoError()
    {
        $this->helper->log('SOME TEXT DATA');

        $content = $this->fileHelper->getLogFileContent();

        $this->assertNotContains('Log file is not readable or does not exist at this moment', $content);
    }
}
