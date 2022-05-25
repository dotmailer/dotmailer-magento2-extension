<?php

namespace Dotdigitalgroup\Email\Model\Integration;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Connector\Datafield;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\TestFramework\ObjectManager;

/**
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class TrialSetupTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    public function setUp() :void
    {
        $this->objectManager = ObjectManager::getInstance(); /** @phpstan-ignore-line */
        $this->configWriter = $this->objectManager->create(WriterInterface::class);
        $this->reinitableConfig = $this->objectManager->create(ReinitableConfigInterface::class);
    }

    /**
     * Test verify code
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testVerifyCode()
    {
        $trialSetup = $this->getIntegrationSetup();
        $code = $trialSetup->generateTemporaryPasscode();
        $this->assertTrue($trialSetup->isCodeValid($code));
    }

    /**
     * Test syncs enabled
     *
     * @throws \ReflectionException
     */
    public function testSyncEnabledForTrial()
    {
        // delete config items, just in case
        foreach ([
            Config::XML_PATH_CONNECTOR_API_USERNAME,
            Config::XML_PATH_CONNECTOR_API_PASSWORD,
            Config::PATH_FOR_API_ENDPOINT,
        ] as $path) {
            $this->configWriter->delete($path);
        }

        $helper = $this->instantiateDataHelper();

        $this->getIntegrationSetup()->enableSyncs();

        $this->reinitableConfig->reinit();

        $this->assertTrue(
            $helper->getWebsiteConfig(Config::XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED)
            && $helper->getWebsiteConfig(Config::XML_PATH_CONNECTOR_SYNC_GUEST_ENABLED)
            && $helper->getWebsiteConfig(Config::XML_PATH_CONNECTOR_SYNC_SUBSCRIBER_ENABLED)
            && $helper->getWebsiteConfig(Config::XML_PATH_CONNECTOR_SYNC_ORDER_ENABLED)
        );
    }

    /**
     * Test create address books
     *
     * @throws \ReflectionException
     */
    public function testCreateAddressBooks()
    {
        $this->setApiConfigFlags([], 0);
        $helper = $this->mockClientFactory()->instantiateDataHelper();
        $this->mockClient->method('getAccountInfo')->willReturn('You are all good!');

        // run create address books method
        $trialSetup = $this->getIntegrationSetup();

        $id = 0;
        $this->mockClient
            ->expects($this->atLeast(count($trialSetup->getAddressBookMap())))
            ->method('postAddressBooks')
            ->will($this->returnCallback(function () use (&$id) {
                return (object) ['id' => $id += 10];
            }));

        $trialSetup->createAddressBooks();
    }

    /**
     * Test signup URL has expected query string params
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testEcSignupUrl()
    {
        $signupUrl = $this->getIntegrationSetup()->getEcSignupUrl(
            $this->objectManager->create(RequestInterface::class),
            IntegrationSetup::SOURCE_CHAT
        );
        $signupUrlParsed = parse_url($signupUrl);
        parse_str($signupUrlParsed['query'], $signupQuery);

        $this->assertStringStartsWith($this->getIntegrationSetup()->getTrialSignupBaseUrl(), $signupUrl);
        $this->assertEquals('http://localhost', $signupQuery['magentohost']);
        $this->assertEquals(IntegrationSetup::SOURCE_CHAT, $signupQuery['source']);
        $this->assertStringEndsWith(Config::MAGENTO_ROUTE, $signupQuery['callback']);
    }

    /**
     * Test that data fields were set
     *
     * @throws \ReflectionException
     */
    public function testSetupDataFields()
    {
        $this->setApiConfigFlags([], 0);

        /** @var Datafield $dataFields */
        $dataFields = $this->objectManager->create(Datafield::class);
        $contactFields = $dataFields->getContactDatafields();
        $this->mockClientFactory();
        $this->mockClient
            ->method('getAccountInfo')
            ->willReturn('Go sell some casuals!');
        $this->mockClient
            ->expects($this->once())
            ->method('getDataFields')
            ->willReturn(['name'=>'dd_chaz_data_field']);
        $this->mockClient->expects($this->atLeast(count($contactFields)))
            ->method('postDataFields')
            ->with($this->logicalOr(...array_values($contactFields)));

        $this->instantiateDataHelper();
        $this->getIntegrationSetup()->setupDataFields();
    }

    /**
     * @return IntegrationSetup
     */
    private function getIntegrationSetup()
    {
        return $this->objectManager->create(IntegrationSetup::class);
    }
}
