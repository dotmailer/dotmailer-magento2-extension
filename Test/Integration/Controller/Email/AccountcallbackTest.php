<?php

namespace Dotdigitalgroup\Email\Controller\Email;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Trial\TrialSetup;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\TestFramework\TestCase\AbstractController;

class AccountcallbackTest extends AbstractController
{
    use MocksApiResponses;

    const TEST_CREDS = [
        'apiusername' => 'chaz@apiconnector.com',
        'apipassword' => 'chazword',
        'apiendpoint' => 'https://api.dotmailer.com/v2',
    ];

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var TrialSetup
     */
    private $trialSetup;

    public function setUp()
    {
        parent::setUp();

        $this->helper = $this->instantiateDataHelper();
        $this->trialSetup = $this->_objectManager->create(TrialSetup::class);
    }

    /**
     * Check 401 is returned with no code
     */
    public function testExecuteNoCode()
    {
        $this->dispatch(Config::MAGENTO_ROUTE);

        $response = $this->getResponse();
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * 401 with invalid code
     */
    public function testExecuteWrongCode()
    {
        $this->getRequest()->setParams([
            'code' => 'wingman',
        ]);
        $this->dispatch(Config::MAGENTO_ROUTE);

        $response = $this->getResponse();
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test a successful request
     */
    public function testSuccessfulRequest()
    {
        $response = $this->sendValidRequest();

        // success status
        $this->assertEquals(201, $response->getStatusCode());

        // EC enabled
        $this->assertTrue((bool) $this->helper->getWebsiteConfig(Config::XML_PATH_CONNECTOR_API_ENABLED));

        // api creds were saved
        $this->assertEquals(
            self::TEST_CREDS['apiusername'],
            $this->helper->getWebsiteConfig(Config::XML_PATH_CONNECTOR_API_USERNAME)
        );
        $this->assertEquals(
            self::TEST_CREDS['apipassword'],
            $this->helper->getWebsiteConfig(Config::XML_PATH_CONNECTOR_API_PASSWORD)
        );
        $this->assertEquals(
            self::TEST_CREDS['apiendpoint'],
            $this->helper->getWebsiteConfig(Config::PATH_FOR_API_ENDPOINT)
        );
    }

    /**
     * Send a valid request
     *
     * @param array $additionalParams
     * @return \Magento\Framework\App\Response\Http|\Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function sendValidRequest(array $additionalParams = [])
    {
        $this->getRequest()->setParams($additionalParams + self::TEST_CREDS + [
            'code' => $this->trialSetup->generateTemporaryPasscode(),
        ]);

        $this->dispatch(Config::MAGENTO_ROUTE);
        return $this->getResponse();
    }
}
