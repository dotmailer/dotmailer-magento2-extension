<?php

namespace Dotdigitalgroup\Email\Helper;

use Magento\TestFramework\ObjectManager;

/**
 * Class ApiEndpointTest
 *
 * @package Dotdigitalgroup\Email\Helper
 * @magentoDBIsolation enabled
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ApiEndpointTest extends \PHPUnit\Framework\TestCase
{
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
        /** @var ObjectManager $objectManager */
        $objectManager = ObjectManager::getInstance();

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

        /** @var \Dotdigitalgroup\Email\Helper\Data $helper */
        $helper = new \Dotdigitalgroup\Email\Helper\Data(
            $objectManager->create(\Magento\Framework\App\ProductMetadata::class),
            $objectManager->create(\Dotdigitalgroup\Email\Model\ContactFactory::class),
            $objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Contact::class),
            $objectManager->create(\Dotdigitalgroup\Email\Helper\File::class),
            $objectManager->create(\Magento\Config\Model\ResourceModel\Config::class),
            $objectManager->create(\Magento\Framework\App\ResourceConnection::class),
            $objectManager->create(\Magento\Framework\App\Helper\Context::class),
            $objectManager->create(\Magento\Store\Model\StoreManagerInterface::class),
            $objectManager->create(\Magento\Customer\Model\CustomerFactory::class),
            $objectManager->create(\Magento\Framework\Module\ModuleListInterface::class),
            $objectManager->create(\Magento\Store\Model\Store::class),
            $objectManager->create(\Magento\Framework\App\Config\Storage\Writer::class),
            $mockClientFactory,
            $objectManager->create(\Dotdigitalgroup\Email\Helper\ConfigFactory::class),
            $objectManager->create(\Dotdigitalgroup\Email\Model\Config\Json::class),
            $objectManager->create(\Magento\Framework\Stdlib\DateTime\DateTime::class),
            $objectManager->create(\Magento\Quote\Model\ResourceModel\Quote::class),
            $objectManager->create(\Magento\Quote\Model\QuoteFactory::class),
            $objectManager->create(\Magento\User\Model\ResourceModel\User::class),
            $objectManager->create(\Magento\Framework\Encryption\EncryptorInterface::class)
        );
        $apiEndpoint = $helper->getApiEndpoint($website, $mockClient);
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
