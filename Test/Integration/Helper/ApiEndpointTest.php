<?php

namespace Dotdigitalgroup\Email\Helper;

use Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory;
use Dotdigitalgroup\Email\Model\DateIntervalFactory;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\TestFramework\ObjectManager;

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
     * @return null
     */
    public function testFetchingApiEndpointSuccessful()
    {
        $endpoint = 'https://api.dotmailer.com/v2';

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
        $apiEndpoint = $helper->getApiEndpoint(1, $this->mockClient);

        $this->assertEquals(
            $endpoint,
            $apiEndpoint
        );
    }
}
