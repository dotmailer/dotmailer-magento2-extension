<?php

namespace Dotdigitalgroup\Email\Model\Events\Response;

use Zend\Http\Response;

class StreamedResponse extends Response
{
    /**
     * The portion of the body that has already been streamed
     *
     * @var int
     */
    protected $contentStreamed = 0;

    /**
     * Response as stream
     *
     * @var callable
     */
    protected $stream;

    /**
     * @var bool
     */
    protected $headersSent = false;

    /**
     * The name of the file containing the stream
     *
     * Will be empty if stream is not file-based.
     *
     * @var string
     */
    protected $streamName;

    /**
     * Array of headers to be applied at the point of SEND
     *
     * @var array
     */
    protected $headers;

    /**
     * @var string
     */
    protected $version;

    /**
     * StreamedResponse Contructor.
     *
     * @param callable $stream
     * @param int $status
     * @param array $headers
     */
    public function __construct(callable $stream, $status = 200, $headers = [])
    {
        $this->setStream($stream);
        $this->statusCode = $status;
        $this->headers = $headers;
    }

    /**
     * Get the response as stream
     *
     * @return callable
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * Set the response stream
     *
     * @param callable $stream
     * @return $this
     */
    public function setStream(callable $stream): StreamedResponse
    {
        $this->stream = $stream;
        return $this;
    }

    /**
     * This method only sends the content once.
     *
     * @return $this
     */
    public function sendContent(): StreamedResponse
    {
        if ($this->contentStreamed) {
            return $this;
        }

        $stream = $this->getStream();
        $this->contentStreamed = true;

        if (null === $stream) {
            throw new \LogicException('The Response callback must not be null.');
        }

        $stream();

        return $this;
    }

    /**
     * Send response
     *
     * @return $this
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();
        return $this;
    }

    /**
     * Set specific header
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     * Get header by key
     *
     * @param string $key
     * @return mixed|null
     */
    public function getHeader($key)
    {
        return (array_key_exists($key, $this->headers)) ? $this->headers[$key] : null;
    }

    /**
     * Set Applicable headers
     *
     * @return $this
     */
    public function sendHeaders()
    {
        if ($this->headersSent) {
            return $this;
        }

        $this->headersSent = true;

        if (headers_sent()) {
            return $this;
        }

        foreach ($this->headers as $name => $value) {
            $replace = 0 === strcasecmp($name, 'Content-Type');
            header($name.': '.$value, $replace, $this->statusCode); // phpcs:ignore

        }

        header( // phpcs:ignore
            sprintf(
                'HTTP/%s %s %s',
                $this->version,
                $this->statusCode,
                $this->statusCode
            ),
            true,
            $this->statusCode
        );

        return $this;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        if (is_callable($this->stream)) {
            $this->stream = null; //Could be listened by others
        }
    }
}
