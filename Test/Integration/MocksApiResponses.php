<?php

namespace Dotdigitalgroup\Email\Test\Integration;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use ReflectionClass;
use ReflectionParameter;

trait MocksApiResponses
{
    /**
     * @var string
     */
    private static $clientFactoryClass = ClientFactory::class;

    /**
     * @var Client
     */
    private $mockClient;

    /**
     * @var ClientFactory
     */
    private $mockClientFactory;

    /**
     * @var MutableScopeConfigInterface
     */
    private $mutableScopeConfig;

    /**
     * The magentoConfigFixture annotation cannot set config at website level
     * Recommend never using docblock annotations in PHP for this, and many other, reasons
     *
     * @param array $configFlags    Overridable config flags
     * @param int $scopeCode        Scope code to set values against
     * @param string $scopeType     Scope type
     */
    private function setApiConfigFlags(
        array $configFlags = [],
        $scopeCode = null,
        $scopeType = ScopeInterface::SCOPE_WEBSITE
    ) {
        foreach ($configFlags + [
            Config::XML_PATH_CONNECTOR_API_ENABLED => 1,
            Config::XML_PATH_CONNECTOR_API_USERNAME => 'test',
            Config::XML_PATH_CONNECTOR_API_PASSWORD => 'test',
            Config::PATH_FOR_API_ENDPOINT => 'https://r1-api.dotmailer.com',
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED => 1,
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS => implode(',', [
                \Magento\Sales\Model\Order::STATE_PROCESSING,
                \Magento\Sales\Model\Order::STATE_COMPLETE,
            ]),
        ] as $path => $value) {
            $this->getMutableScopeConfig()->setValue($path, $value, $scopeType, $scopeCode);
        }
    }

    /**
     * @return MutableScopeConfigInterface
     */
    private function getMutableScopeConfig()
    {
        return $this->mutableScopeConfig
            ?: $this->mutableScopeConfig = Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class);
    }

    /**
     * Due to Magento not accepting a mocked generated class with ObjectManager::addSharedInstance, we have
     * gone to incredible lengths to mock the Client
     *
     * @param array $parameters     [\Class\Name => instance of class] to replace default DI when instantiating Data
     * @return Data
     * @throws \ReflectionException
     */
    private function instantiateDataHelper(array $parameters = [])
    {
        $objectManager = Bootstrap::getObjectManager();
        $class = new ReflectionClass(Data::class);

        // add mock clientfactory, if it has been mocked
        if ($this->mockClient && $this->mockClientFactory) {
            $parameters += [
                self::$clientFactoryClass => $this->mockClientFactory,
            ];
        }

        // build all constructor parameters, sneaking in any overridden parameters
        $args = array_map(function ($param) use ($objectManager, $parameters) {
            /** @var ReflectionParameter $param */
            if (array_key_exists($param->getClass()->getName(), $parameters)) {
                return $parameters[$param->getClass()->getName()];
            }
            return $objectManager->create($param->getClass()->getName());
        }, $class->getConstructor()->getParameters());

        // share a pre-generated data helper
        /** @var Data $helper */
        $helper = $class->newInstanceArgs($args);
        $objectManager->addSharedInstance($helper, Data::class);

        return $helper;
    }

    /**
     * Generate a mock of Client and ClientFactory which creates it
     *
     * @return $this
     */
    private function mockClientFactory()
    {
        $this->mockClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(get_class_methods(Client::class))
            ->getMock();
        $this->mockClient->method('setApiUsername')
            ->willReturn(new class() {
                public function setApiPassword($password)
                {
                }
            });

        $this->mockClientFactory = $this->getMockBuilder(self::$clientFactoryClass)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockClientFactory->method('create')->willReturn($this->mockClient);

        return $this;
    }
}
