<?php

namespace Dotdigitalgroup\Email\Helper;

use Dotdigitalgroup\Email\Helper\Data;
use Magento\TestFramework\ObjectManager;
use Dotdigitalgroup\Email\Logger\Logger;
use Monolog\Handler\TestHandler;

class LoggerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var TestHandler
     */
    private $testHandler;

    /**
     * @return void
     */
    public function setup()
    {
        $objectManager = ObjectManager::getInstance();
        $this->testHandler = new TestHandler;

        $this->logger = $objectManager->get(Logger::class);
        $this->logger->pushHandler($this->testHandler);
        $this->helper = $objectManager->get(Data::class);
    }

    public function testInfoWasLogged()
    {
        $record = 'info data';
        $this->helper->log($record);
        $this->assertTrue($this->testHandler->hasInfo($record));
    }

    public function testErrorWasLogged()
    {
        $record = 'error data';
        $this->helper->error($record);
        $this->assertTrue($this->testHandler->hasError($record));
    }

    public function testDebugWasLogged()
    {
        $record = 'debug data';
        $this->helper->debug($record);
        $this->assertTrue($this->testHandler->hasDebug($record));
    }

    public function testDebugWithExtra()
    {
        $record = [
            'message' => 'debug data with context',
            'context' => [
                'chaz' => 'kangaroo',
            ],
        ];
        $this->helper->debug($record['message'], $record['context']);
        $this->assertTrue($this->testHandler->hasDebug($record));
    }
}
