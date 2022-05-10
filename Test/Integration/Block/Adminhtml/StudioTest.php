<?php

namespace Dotdigitalgroup\Email\Test\Integration\Block\Adminhtml;

use Dotdigitalgroup\Email\Block\Adminhtml\Studio;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\OauthValidator;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetup;
use Dotdigitalgroup\Email\Model\Integration\IntegrationSetupFactory;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Auth;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Oauth\Oauth;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Backend\Model\Auth\Credential\StorageInterface;
use Magento\TestFramework\ObjectManager;

class StudioTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

    /**
     * @var Studio
     */
    private $studio;

    /**
     * @var Auth
     */
    private $authMock;

    /**
     * @var StorageInterface
     */
    private $storageInterfaceMock;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var IntegrationSetup
     */
    private $integrationSetup;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var OauthValidator
     */
    private $oauthValidator;

    /**
     * @var StorageInterface
     */
    private $userMock;

    public function setUp() :void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->mockClientFactory();

        $this->authMock = $this->createMock(Auth::class);
        $this->userMock = $this->getMockBuilder(StorageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(array_merge(get_class_methods(StorageInterface::class), ['getRefreshToken']))
            ->getMock();

        $objectManager->addSharedInstance($this->authMock, Auth::class);
        $helper = $this->instantiateDataHelper();

        $this->integrationSetup = $objectManager->get(IntegrationSetup::class);
        $this->oauthValidator = $objectManager->create(OauthValidator::class);

        $integrationFactoryMock = $this->createMock(IntegrationSetupFactory::class);
        $integrationFactoryMock->method('create')
            ->willReturn($this->integrationSetup);

        $this->config = $objectManager->create(Config::class);

        $this->studio = new Studio(
            $this->config,
            $objectManager->create(Context::class),
            $helper,
            $integrationFactoryMock,
            $this->oauthValidator
        );
    }

    /**
     * Assert that the signup microsite URL is return if no API creds are available
     */
    public function testEcLoginNoCreds()
    {
        $url = parse_url($this->studio->getAction());
        $this->assertStringStartsWith(
            $this->integrationSetup->getTrialSignupHostAndScheme(),
            sprintf('%s://%s', $url['scheme'], $url['host'])
        );
    }

    /**
     * Assert that the login page is shown with no oauth token
     */
    public function testEcLoginNoOauthCreds()
    {
        $this->setApiConfigFlags([], 0);

        $this->authMock->expects($this->once())
            ->method('getUser')
            ->willReturn($this->userMock);
        $this->userMock->expects($this->once())
            ->method('getRefreshToken')
            ->willReturn(null);

        $url = $this->studio->getAction();
        $this->assertStringStartsWith($this->config->getLoginUserUrl(), $url);
        $this->assertStringNotContainsString(Config::API_CONNECTOR_OAUTH_URL_LOG_USER, parse_url($url, PHP_URL_QUERY));
    }

    /**
     * Assert login returned with oauth creds in query
     */
    public function testEcLoginOauthCreds()
    {
        $this->setApiConfigFlags([
            Config::XML_PATH_CONNECTOR_CLIENT_ID => '1234567',
            Config::XML_PATH_CONNECTOR_CLIENT_SECRET_ID => 'datatifileds',
        ], 0);

        $token = 'chazkangaroo';

        $this->mockClient->expects($this->once())
            ->method('getAccessToken')
            ->willReturn($token);
        $this->authMock->expects($this->once())
            ->method('getUser')
            ->willReturn($this->userMock);
        $this->userMock->expects($this->once())
            ->method('getRefreshToken')
            ->willReturn('hangleSalesOrderCancel');

        $url = $this->studio->getAction();
        $this->assertStringContainsString(
            sprintf('%s=%s', Config::API_CONNECTOR_OAUTH_URL_LOG_USER, $token),
            parse_url($url, PHP_URL_QUERY)
        );
    }
}
