<?php

namespace Dotdigitalgroup\Email\Test\Unit\ServerSentEvent;

use Dotdigitalgroup\Email\Helper\ServerSentEvents;
use Dotdigitalgroup\Email\Logger\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\TestCase;

class ServerSentEventTest extends TestCase
{

    public function testConstructor()
    {
        $logger = new Logger('tests');
        $init_request = Request::create('/', 'GET', [], [], [], []);
        $sse = new ServerSentEvents($init_request,$logger);
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
        $sse = new ServerSentEvents($init_request,$logger);
        $response = $sse->createResponse();

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\StreamedResponse', $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/event-stream', $response->headers->get('Content-Type'));

        $sse->allow_cors = true;
        $response = $sse->createResponse();
        $this->assertEquals('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('true', $response->headers->get('Access-Control-Allow-Credentials'));

    }

    public function testGetEventListener()
    {
        $logger = new Logger('tests');
        $init_request = Request::create('/', 'GET', [], [], [], []);
        $sse = new ServerSentEvents($init_request,$logger);
        $this->assertEquals([], $sse->getEventHandlers());
    }

}

