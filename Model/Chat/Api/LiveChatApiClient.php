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
     * @var HttpClientFactory
     */
    private $httpClientFactory;

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
        $this->httpClientFactory = $clientFactory;
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

        /** @var HttpClient $httpClient */
        $httpClient = $this->httpClientFactory->create();
        $httpClient->setMethod($method)
            ->setUri(sprintf('%s/%s', self::CHAT_API_HOST, $endpoint))
            ->setHeaders([
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiToken,
            ]);

        // add JSON body, if required
        if (!empty($body)) {
            $httpClient->setRawBody(json_encode($body));
        }
        return $httpClient->send();
    }
}
