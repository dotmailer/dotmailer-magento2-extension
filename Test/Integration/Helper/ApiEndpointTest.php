<?php

namespace Dotdigitalgroup\Email\Helper;

use Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory;
use Dotdigitalgroup\Email\Model\DateIntervalFactory;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\TestFramework\ObjectManager;

/**
 * Class ApiEndpointTest
 *
 * @package Dotdigitalgroup\Email\Helper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApiEndpointTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

    /**
     * @return void
     */
    public function setup()
    {
        $this->removeData();
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        $this->removeData();
    }

    /**
     * @return void
     */
    public function removeData()
    {
        /** @var ObjectManager $objectManager */
        $objectManager = ObjectManager::getInstance();

        /** @var \Magento\Config\Model\ResourceModel\Config $config */
        $config = $objectManager->create(\Magento\Config\Model\ResourceModel\Config::class);
        $data = $this->dataProvider();

        foreach ($data as $item) {
            $config->deleteConfig(Config::PATH_FOR_API_ENDPOINT, $item[2], $item[0]);
        }
    }

    /**
     * @param int $website
     * @param string $endPoint
     *
     * @return null
     *
     * @dataProvider dataProvider
     */
    public function testFetchingApiEndpointSuccessful($website, $endPoint)
    {
        $property = new \stdClass();
        $property->name = 'ApiEndpoint';
        $property->value = $endPoint;

        $accountInfo = new \stdClass();
        $accountInfo->properties = [$property];

        $mockClient = $this->getMockBuilder(\Dotdigitalgroup\Email\Model\Apiconnector\Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockClient->method('getAccountInfo')
            ->willReturn($accountInfo);

        $mockClientFactory = $this->getMockBuilder(
            \Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $mockClientFactory->method('create')
            ->willReturn($mockClient);

        $this->setApiConfigFlags([
            Config::PATH_FOR_API_ENDPOINT => null,
        ]);
        $this->instantiateDataHelper([
            ClientFactory::class => $mockClientFactory,
        ]);

        $apiEndpoint = ObjectManager::getInstance()->create(Data::class)
            ->getApiEndpoint($website, $mockClient);

        $this->assertEquals(
            $endPoint,
            $apiEndpoint
        );
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            [
                0,
                'https://r1.dummy.com',
                'default'
            ],
            [
                1,
                'https://r1.dummy.com',
                'website'
            ]
        ];
    }
}
