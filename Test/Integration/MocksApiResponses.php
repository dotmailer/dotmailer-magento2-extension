<?php

namespace Dotdigitalgroup\Email\Test\Integration;

use Dotdigitalgroup\Email\Helper\Config;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use ReflectionClass;
use ReflectionParameter;
use Dotdigitalgroup\Email\Helper\Data;
use Magento\TestFramework\Helper\Bootstrap;

trait MocksApiResponses
{
    /**
     * The magentoConfigFixture annotation cannot set config at website level
     * Recommend never using docblock annotations in PHP for this, and many other, reasons
     *
     * @param array $configFlags    Overridable config flags
     */
    private function setApiConfigFlags(array $configFlags = [])
    {
        /** @var MutableScopeConfigInterface $mutableScopeConfig */
        $mutableScopeConfig = Bootstrap::getObjectManager()->get(MutableScopeConfigInterface::class);
        foreach ($configFlags + [
            Config::XML_PATH_CONNECTOR_API_ENABLED => 1,
            Config::XML_PATH_CONNECTOR_API_USERNAME => 'test',
            Config::XML_PATH_CONNECTOR_API_PASSWORD => 'test',
            Config::PATH_FOR_API_ENDPOINT => 'https://dotdigital.com',
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED => 1,
            Config::XML_PATH_CONNECTOR_SYNC_ORDER_STATUS => implode(',', [
                \Magento\Sales\Model\Order::STATE_PROCESSING,
                \Magento\Sales\Model\Order::STATE_COMPLETE,
            ]),
        ] as $path => $value) {
            $mutableScopeConfig->setValue($path, $value, ScopeInterface::SCOPE_WEBSITE);
        }
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
}