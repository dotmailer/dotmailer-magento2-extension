<?php

namespace Dotdigitalgroup\Email\Model\Chat\Api;

use Dotdigitalgroup\Email\Model\Chat\Config;
use Zend\Http\Client as HttpClient;
use Zend\Http\ClientFactory;
use Zend\Http\Response;

class LiveChatApiClient
{
    /**
     * Chat API hostname
     */
    const CHAT_API_HOST = 'https://api.comapi.com';

    /**
     * Chat config
     *
     * @var Config
     */
    private $config;

    /**
     * Zend HTTP Client
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Client constructor
     *
     * @param Config $config
     * @param ClientFactory $clientFactory
     */
    public function __construct(
        Config $config,
        ClientFactory $clientFactory
    ) {
        $this->config = $config;
        $this->httpClient = $clientFactory->create();
    }

    /**
     * Send a request to the Chat API
     *
     * @param string $endpoint
     * @param string $method
     * @param array $body
     * @param string $apiToken
     * @return Response
     */
    public function request($endpoint, $method, array $body = [], $apiToken = null)
    {
        // set up client
        $apiToken = $apiToken ?: $this->config->getApiToken();
        $this->httpClient->setMethod($method)
            ->setUri(sprintf('%s/%s', self::CHAT_API_HOST, $endpoint))
            ->setHeaders([
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiToken,
            ]);

        // add JSON body, if required
        if (!empty($body)) {
            $this->httpClient->setRawBody(json_encode($body));
        }
        return $this->httpClient->send();
    }
}
