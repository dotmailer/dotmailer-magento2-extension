<?php

namespace Dotdigitalgroup\Email\Model\Chat\Api\Requests;

use Dotdigitalgroup\Email\Model\Chat\Api\LiveChatApiClient;
use Dotdigitalgroup\Email\Model\Chat\Api\LiveChatRequestInterface;
use Dotdigitalgroup\Email\Model\Chat\Config;
use Zend\Http\Request;

class UpdateProfile implements LiveChatRequestInterface
{
    /**
     * @var LiveChatApiClient
     */
    private $client;

    /**
     * @var Config
     */
    private $config;

    /**
     * UpdateProfile constructor
     *
     * @param LiveChatApiClient $client
     * @param Config $config
     */
    public function __construct(LiveChatApiClient $client, Config $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * @param string $profileId
     * @param array $data
     * @return \Zend\Http\Response
     */
    public function send(string $profileId, array $data = [])
    {
        return $this->client->request(
            sprintf('apispaces/%s/profiles/%s', $this->config->getApiSpaceId(), $profileId),
            Request::METHOD_PATCH,
            $data
        );
    }
}
