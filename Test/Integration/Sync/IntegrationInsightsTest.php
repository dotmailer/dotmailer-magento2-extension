<?php

namespace Dotdigitalgroup\Email\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Sync\IntegrationInsights;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\ObjectManager;

class IntegrationInsightsTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

    /**
     * @var MutableScopeConfigInterface
     */
    private $mutableScopeConfig;

    public function setUp()
    {
        $this->mutableScopeConfig = ObjectManager::getInstance()->get(MutableScopeConfigInterface::class);
    }

    /**
     * Sync runs when enabled
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testSyncEnabled()
    {
        /** @var IntegrationInsights $integrationInsights */
        $integrationInsights = ObjectManager::getInstance()->create(IntegrationInsights::class);
        $this->mutableScopeConfig->setValue(Config::XML_PATH_CONNECTOR_INTEGRATION_INSIGHTS_ENABLED, 1, 'default');
        $this->assertTrue($integrationInsights->sync());
    }

    /**
     * And doesn't when disabled
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testSyncDisabled()
    {
        /** @var IntegrationInsights $integrationInsights */
        $integrationInsights = ObjectManager::getInstance()->create(IntegrationInsights::class);
        $this->mutableScopeConfig->setValue(Config::XML_PATH_CONNECTOR_INTEGRATION_INSIGHTS_ENABLED, 0, 'default');
        $this->assertFalse($integrationInsights->sync());
    }

    /**
     * Assert that the data is sent to EC
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \ReflectionException
     */
    public function testClientDataSent()
    {
        $this->setApiConfigFlags();
        $this->mutableScopeConfig->setValue(Config::XML_PATH_CONNECTOR_INTEGRATION_INSIGHTS_ENABLED, 1, 'default');

        $this->mockClientFactory();
        $this->mockClient->expects($this->once())
            ->method('postIntegrationInsightData');

        $this->instantiateDataHelper();

        /** @var IntegrationInsights $integrationInsights */
        $integrationInsights = ObjectManager::getInstance()->create(IntegrationInsights::class);
        $integrationInsights->sync();
    }
}
