<?php

namespace Dotdigitalgroup\Email\Logger\Handler;

use Monolog\Logger;

class ConnectorLogHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = '/var/log/connector.log';
}
