<?php

namespace Dotdigitalgroup\Email\Logger\Handler;

use Monolog\Logger;

class Info extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/connector.log';
}
