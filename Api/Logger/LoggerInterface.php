<?php

namespace Dotdigitalgroup\Email\Api\Logger;

use Monolog\Handler\HandlerInterface;

interface LoggerInterface extends \Psr\Log\LoggerInterface
{
    /**
     * Logs with an arbitrary level.
     *
     * Logs a message with a given level and context.
     *
     * @param mixed  $level   The log level
     * @param string $message The log message
     * @param array  $context The log context
     *
     * @return void
     */
    public function log($level, $message, array $context = []): void;

    /**
     * Adds a log record at an arbitrary level.
     *
     * Adds a log record with a given level, message, and context.
     *
     * @param mixed  $level   The log level
     * @param string $message The log message
     * @param array  $context The log context
     *
     * @return void
     */
    public function addRecord($level, $message, array $context = []): void;

    /**
     * Pushes a handler on to the stack.
     *
     * @param HandlerInterface $handler
     *
     * @return $this
     */
    public function pushHandler(HandlerInterface $handler): self;
}
