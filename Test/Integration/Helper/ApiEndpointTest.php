<?php

namespace Dotdigitalgroup\Email\Helper;

use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;

/**
 * @magentoDbIsolation enabled
 */
class ApiEndpointTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

    /**
     * @param int $website
     * @param string $endPoint
     *
     * @return void
     */
    public function testFetchingApiEndpointSuccessful()
    {
        $endpoint = 'https://r1-api.dotdigital.com';

        $this->mockClientFactory();
        $this->mockClient->method('getAccountInfo')
            ->willReturn((object) [
                'properties' => [(object) [
                    'name' => 'ApiEndpoint',
                    'value' => $endpoint,
                ]],
            ]);

        $this->setApiConfigFlags([
            Config::PATH_FOR_API_ENDPOINT => null,
        ]);

        $helper = $this->instantiateDataHelper();
        $apiEndpoint = $helper->getApiEndPointFromConfig(1);

        $this->assertEquals(
            $endpoint,
            $apiEndpoint
        );
    }
}
