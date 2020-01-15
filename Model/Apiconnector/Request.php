<?php

namespace Dotdigitalgroup\Email\Model\Apiconnector;

use Zend\Http\ClientFactory;
use Zend\Http\Client as HttpClient;
use Zend\Http\Response;

class Request
{
    /**
     * @var ClientFactory
     */
    private $clientFactory;

    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var string
     */
    private $encType;

    /**
     * @var float
     */
    private $requestTime;

    /**
     * @param ClientFactory $clientFactory
     */
    public function __construct(ClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    /**
     * @param string $uri
     * @param string $method
     * @param string|null $apiUsername
     * @param string|null $apiPassword
     * @param string|null $encType
     * @return $this
     */
    public function prepare(
        string $uri,
        string $method,
        string $apiUsername = null,
        string $apiPassword = null,
        string $encType = null
    ) {
        $client = $this->getClient()
            ->setUri($this->uri = $uri)
            ->setMethod($method);

        if ($apiUsername && $apiPassword) {
            $client->setAuth($apiUsername, $apiPassword);
        }

        $this->encType = $encType;

        return $this;
    }

    /**
     * @param string $verb
     * @return $this
     */
    public function setMethod(string $verb)
    {
        $this->getClient()
            ->setMethod($verb);

        return $this;
    }

    /**
     * @param string $encType
     * @return $this
     */
    public function setEncType(string $encType)
    {
        $this->encType = $encType;
        return $this;
    }

    /**
     * @param string $filePath
     * @param string $mimeType
     * @return $this
     * @throws \ErrorException
     */
    public function addFile(string $filePath, string $mimeType = 'text/csv')
    {
        if ($this->file) {
            throw new \ErrorException('Only one file can be uploaded per request');
        }

        $this->getClient()
            ->setFileUpload($this->file = $filePath, 'file', null, $mimeType);

        return $this;
    }

    /**
     * @param array|null $requestBody
     * @return Response
     * @throws \ErrorException
     */
    public function send(array $requestBody = null)
    {
        if (empty($this->getClient()->getUri())) {
            throw new \ErrorException('Request has not been prepared');
        }

        $client = $this->getClient();
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($requestBody) {
            $client->getRequest()->setContent(
                $this->encType == HttpClient::ENC_URLENCODED
                    ? http_build_query($requestBody)
                    : json_encode($requestBody)
            );
            $headers['Content-Type'] = $this->encType ?: 'application/json';
        } elseif ($this->file) {
            $headers['Content-Disposition'] = sprintf('attachment; name="fileData", filename="%s"', basename($this->file));
        }

        $client->setHeaders($headers);

        $requestStart = microtime(true);
        $response = $client->send();
        $this->requestTime = microtime(true) - $requestStart;

        return $response;
    }

    /**
     * @return float
     */
    public function getRequestTime()
    {
        return $this->requestTime;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return HttpClient
     */
    private function getClient()
    {
        return $this->client
            ?: $this->client = $this->clientFactory->create();
    }
}
