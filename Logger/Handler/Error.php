<?php

namespace Dotdigitalgroup\Email\Logger\Handler;

use Monolog\Logger;

class Error extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::ERROR;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/connector.log';
}
