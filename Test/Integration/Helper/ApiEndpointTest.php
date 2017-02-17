<?php

namespace Dotdigitalgroup\Email\Helper;

use Magento\TestFramework\ObjectManager;

/**
 * Class ApiEndpointTest
 *
 * @package Dotdigitalgroup\Email\Helper
 * @magentoDBIsolation enabled
 * @magentoAppArea frontend
 */
class ApiEndpointTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $website
     * @param $username
     * @param $password
     * @param $scope
     *
     * @dataProvider dataProvider
     */
    public function test_fetching_api_endpoint_successful($website, $username, $password, $scope)
    {
        /** @var ObjectManager $objectManager */
        $objectManager = ObjectManager::getInstance();

        $property = new \stdClass();
        $property->name = 'ApiEndpoint';
        $property->value = 'https://dummy.com';

        $accountInfo = new \stdClass();
        $accountInfo->properties = [$property];

        $mockClient = $this->getMock(\Dotdigitalgroup\Email\Model\Apiconnector\Client::class, [], [], '', false);
        $mockClient->method('setApiUsername')
            ->willReturn($mockClient);
        $mockClient->method('setApiPassword')
            ->willReturn($mockClient);
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
        $helper->getWebsiteApiClient($website, $username, $password);
        $this->assertEquals(
            'https://dummy.com',
            $helper->getWebsiteConfig(\Dotdigitalgroup\Email\Helper\Config::PATH_FOR_API_ENDPOINT, $website, $scope)
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
                'DUMMY-USERNAME',
                'DUMMY-PASSWORD',
                'default'
            ],
            [
                1,
                'DUMMY-USERNAME',
                'DUMMY-PASSWORD',
                'website'
            ]
        ];
    }
}