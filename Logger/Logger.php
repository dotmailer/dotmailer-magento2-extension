<?php

namespace Dotdigitalgroup\Email\Logger;

use Dotdigitalgroup\Email\Api\Logger\LoggerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerTrait;

class Logger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    public const WARNING = MonologLogger::WARNING;

    /**
     * Runtime errors
     */
    public const ERROR = MonologLogger::ERROR;

    /**
     * Detailed debug information
     */
    public const DEBUG = MonologLogger::DEBUG;

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    public const INFO = MonologLogger::INFO;

    /**
     * @var MonologLogger
     */
    private $logger;

    /**
     * DotdigitalLogger constructor.
     *
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(string $name, array $handlers = [], array $processors = [])
    {
        $this->logger = new MonologLogger($name, $handlers, $processors);
    }

    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function addRecord($level, $message, array $context = []): void
    {
        $this->logger->addRecord($level, $message, $context);
    }

    /**
     * @inheritdoc
     */
    public function pushHandler(HandlerInterface $handler): self
    {
        $this->logger->pushHandler($handler);
        return $this;
    }
}
