<?php

namespace Dotdigitalgroup\Email\Helper;

use Magento\TestFramework\ObjectManager;

/**
 * Class ApiEndpointTest
 *
 * @package Dotdigitalgroup\Email\Helper
 * @magentoDBIsolation enabled
 */
class ApiEndpointTest extends \PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $this->removeData();
    }

    public function tearDown()
    {
        $this->removeData();
    }

    public function removeData()
    {
        /** @var ObjectManager $objectManager */
        $objectManager = ObjectManager::getInstance();

        /** @var \Magento\Config\Model\ResourceModel\Config $config */
        $config = $objectManager->create('Magento\Config\Model\ResourceModel\Config');
        $data = $this->dataProvider();

        foreach ($data as $item) {
            $config->deleteConfig(Config::PATH_FOR_API_ENDPOINT, $item[2], $item[0]);
        }
    }

    /**
     * @param $website
     * @param $endPoint
     *
     * @dataProvider dataProvider
     */
    public function test_fetching_api_endpoint_successful($website, $endPoint)
    {
        /** @var ObjectManager $objectManager */
        $objectManager = ObjectManager::getInstance();

        $property = new \stdClass();
        $property->name = 'ApiEndpoint';
        $property->value = $endPoint;

        $accountInfo = new \stdClass();
        $accountInfo->properties = [$property];

        $mockClient = $this->getMock(\Dotdigitalgroup\Email\Model\Apiconnector\Client::class, [], [], '', false);
        $mockClient->method('getAccountInfo')
            ->willReturn($accountInfo);

        $mockClientFactory = $this->getMock(\Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory::class, [], [], '', false);
        $mockClientFactory->method('create')
            ->willReturn($mockClient);

        /** @var \Dotdigitalgroup\Email\Helper\Data $helper */
        $helper = new \Dotdigitalgroup\Email\Helper\Data(
            $objectManager->create('\Magento\Framework\App\ProductMetadata'),
            $objectManager->create('\Dotdigitalgroup\Email\Model\ContactFactory'),
            $objectManager->create('\Dotdigitalgroup\Email\Helper\File'),
            $objectManager->create('\Magento\Config\Model\ResourceModel\Config'),
            $objectManager->create('\Magento\Framework\App\ResourceConnection'),
            $objectManager->create('\Magento\Framework\App\Helper\Context'),
            $objectManager->create('\Magento\Store\Model\StoreManagerInterface'),
            $objectManager->create('\Magento\Customer\Model\CustomerFactory'),
            $objectManager->create('\Magento\Framework\Module\ModuleListInterface'),
            $objectManager->create('\Magento\Cron\Model\ScheduleFactory'),
            $objectManager->create('\Magento\Store\Model\Store'),
            $objectManager->create('\Magento\Framework\App\Config\Storage\Writer'),
            $mockClientFactory,
            $objectManager->create('\Dotdigitalgroup\Email\Helper\ConfigFactory')
        );
        $apiEndpoint = $helper->getApiEndpoint($website, $mockClient);
        $this->assertEquals(
            $endPoint,
            $apiEndpoint
        );
    }

    /**
     *
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