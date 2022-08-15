<?php

namespace Dotdigitalgroup\Email\Test\Integration\Helper;

use Dotdigitalgroup\Email\Helper\ServerSentEvents;
use Dotdigitalgroup\Email\Logger\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ServerSentEventsTest extends TestCase
{
    public function testConstructor()
    {
        $logger = new Logger('tests');
        $init_request = Request::create('/', 'GET', [], [], [], []);
        $sse = new ServerSentEvents($init_request, $logger);
        $this->assertEquals(true, $sse->client_reconnect);
        $this->assertEquals(false, $sse->allow_cors);
        $this->assertEquals(false, $sse->is_reconnect);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateResponse()
    {
        $logger = new Logger('tests');
        $init_request = Request::create('/', 'GET', [], [], [], []);
        $sse = new ServerSentEvents($init_request, $logger);
        $response = $sse->createResponse();

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\StreamedResponse', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/event-stream', $response->headers->get('Content-Type'));

        $sse->allow_cors = true;
        $response = $sse->createResponse();
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('true', $response->headers->get('Access-Control-Allow-Credentials'));

        /**
         * To prevent the warning:
         * Test code or tested code did not (only) close its own output buffers.
         *
         * ob_get_level() returns 1 at the start of the test, and must be 1 at the end.
         */
        ob_start();
    }

    public function testGetEventListener()
    {
        $logger = new Logger('tests');
        $init_request = Request::create('/', 'GET', [], [], [], []);
        $sse = new ServerSentEvents($init_request, $logger);
        $this->assertEquals([], $sse->getEventHandlers());
    }
}

