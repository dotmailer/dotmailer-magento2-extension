<?php

namespace Dotdigitalgroup\Email\Helper;

use Dotdigitalgroup\Email\Model\Events\EventInterface;
use Dotdigitalgroup\Email\Model\Events\Response\StreamedResponse;
use Zend\Http\Response;
use Zend\Http\Request;
use Magento\Setup\Exception;
use Psr\Log\LoggerInterface;

/**
 * @property int $client_reconnect
 * @property bool $allow_cors
 * @property bool $is_reconnect
 */
class ServerSentEvents
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $handlers = [];

    /**
     * Event ID.
     *
     * @var int
     */
    private $id = 0;

    /**
     * Config Setting
     * @var array
     */
    private $config = [
        'client_reconnect' => 1,            // the time client to reconnect after connection has lost in seconds
        'allow_cors' => false,              // Allow Cross-Origin Access?
        'is_reconnect' => false            // A read-only flag indicates whether the user reconnects
    ];

    /**
     * @param Request $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        Request $request,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->id = (int)$request->getHeaders('Last-Event-ID', 0);
        $this->config['is_reconnect'] = $request->getHeaders()->has('Last-Event-ID');
    }

    /**
     * Attach a event handler
     *
     * @param string $event the event name
     * @param EventInterface $handler the event handler
     * @return ServerSentEvents
     */
    public function addEventHandler(string $event, EventInterface $handler)
    {
        $this->handlers[$event] = $handler;
        return $this;
    }

    /**
     * Get all the listeners
     *
     * @return array
     */
    public function getEventHandlers()
    {
        return $this->handlers;
    }

    /**
     * Send Data in buffer to client
     */
    private function flush()
    {
        try {
            flush();
        } catch (Exception $exception) {
            $this->logger->debug("Buffer Error:", ['exception' => $exception]);
        }
    }

    /**
     * Send Data
     *
     * @param string $content
     */
    private function send(string $content)
    {
        ob_start(function () use ($content) { // phpcs:ignore
            return $content;
        });
        ob_end_flush();
    }

    /**
     * Send a SSE data block
     *
     * @param mixed $id Event ID
     * @param string $data Event Data
     * @param string $name Event Name
     */
    private function sendBlock($id, $data, $name = null)
    {
        $this->send("id: {$id}\n");
        if (strlen($name) && $name !== null) {
            $this->send("event: {$name}\n");
        }

        $this->send($this->wrapData($data) . "\n\n");
    }

    /**
     * Create SSE data string
     *
     * @param string $string data to be processed
     * @return string
     */
    private function wrapData($string)
    {
        return 'data:' . str_replace("\n", "\ndata: ", $string);
    }

    /**
     * Returns a streamed response.
     *
     * @return StreamedResponse
     */
    public function createResponse()
    {
        try {
            $this->init();
        } catch (Exception $exception) {
            $this->logger->debug("Buffer Error:", ['exception' => $exception]);
        }
        $that = $this;
        $callback = function () use ($that) {
            // Set the retry interval for the client
            $this->send('retry: ' . ($that->client_reconnect * 1000) . "\n");
            // Start to check for updates
            foreach ($that->getEventHandlers() as $event => $handler) {
                $data = $handler->update(); // Get the data
                $id = $that->getNewId();
                $that->sendBlock($id, $data, $event);
                // Make sure the data has been sent to the client
                $that->flush();
            }
        };
        $response = new StreamedResponse($callback, Response::STATUS_CODE_200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no' // Disables FastCGI Buffering on Nginx
        ]);

        if ($this->allow_cors) {
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }
        return $response;
    }

    /**
     * Get the id for new message
     *
     * @return int
     */
    private function getNewId()
    {
        $this->id += 1;
        return $this->id;
    }

    /**
     * Initial System
     *
     * @return void
     */
    protected function init()
    {
        // Disable time limit
        set_time_limit(0); // phpcs:ignore
        // Prevent buffering
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', 1);// phpcs:ignore
        }
        ini_set('zlib.output_compression', 0);// phpcs:ignore
        ini_set('implicit_flush', 1);// phpcs:ignore
        while (ob_get_level() != 0) {
            ob_end_flush();
        }
        ob_implicit_flush(1);
    }

    /**
     * Get config of SSE
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->config[$key];
    }

    /**
     * Get config of SSE
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Set config of SSE
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    private function set($key, $value)
    {
        if (in_array($key, ['is_reconnect'])) {
            throw new \InvalidArgumentException('is_reconnected is an read-only flag');
        }
        $this->config[$key] = $value;
    }

    /**
     * Set config of SSE
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }
}
