<?php

namespace Dotdigitalgroup\Email\Test\Integration\Helper;

use Dotdigitalgroup\Email\Helper\ServerSentEvents;
use Dotdigitalgroup\Email\Logger\Logger;
use PHPUnit\Framework\TestCase;
use Zend\Http\Request;

class ServerSentEventsTest extends TestCase
{
    public function testConstructor()
    {
        $logger = new Logger('tests');
        $init_request = new Request();
        $sse = new ServerSentEvents($init_request, $logger);
        $this->assertEquals(true, $sse->client_reconnect);
        $this->assertEquals(false, $sse->allow_cors);
        $this->assertEquals(false, $sse->is_reconnect);
    }

    public function testCreateResponse()
    {
        $logger = new Logger('tests');
        $init_request = new Request();
        $sse = new ServerSentEvents($init_request, $logger);
        $response = $sse->createResponse();

        $this->assertInstanceOf('\Dotdigitalgroup\Email\Model\Events\Response\StreamedResponse', $response);
        $this->assertEquals('text/event-stream', $response->getHeader('Content-Type'));

        $sse->allow_cors = true;
        $response = $sse->createResponse();
        $this->assertEquals('*', $response->getHeader('Access-Control-Allow-Origin'));
        $this->assertEquals('true', $response->getHeader('Access-Control-Allow-Credentials'));

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
        $init_request = new Request();
        $sse = new ServerSentEvents($init_request, $logger);
        $this->assertEquals([], $sse->getEventHandlers());
    }
}

