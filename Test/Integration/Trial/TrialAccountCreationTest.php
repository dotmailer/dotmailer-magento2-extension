<?php

namespace Dotdigitalgroup\Email\Controller\Email;


use Magento\TestFramework\ObjectManager;
use Dotdigitalgroup\Email\Helper\Config;

/**
 * Class TrailAccountCreationTest
 * @package Dotdigitalgroup\Email\Controller\Email
 * @magentoDBIsolation enabled
 */
class TrialAccountCreationTest extends \Magento\TestFramework\TestCase\AbstractController
{
    public function setup()
    {
        parent::setup();
        $this->removeData();
    }

    public function tearDown()
    {
        $this->removeData();
    }

    public function removeData()
    {
        /** @var \Magento\Config\Model\ResourceModel\Config $config */
        $config = $this->_objectManager->create('Magento\Config\Model\ResourceModel\Config');
        $config->deleteConfig(Config::XML_PATH_CONNECTOR_API_ENABLED, 'default', 0);
        $config->deleteConfig(Config::XML_PATH_CONNECTOR_API_USERNAME, 'default', 0);
        $config->deleteConfig(Config::XML_PATH_CONNECTOR_API_PASSWORD, 'default', 0);
    }

    /**
     * @param $apiUser
     * @param $apiPass
     * @dataProvider apiDetailsDataProvider
     */
    public function test_trial_account_created_successfully($apiUser, $apiPass)
    {
        $mockRemoteAddress = $this->getMock('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress', [], [], '', false);
        $mockRemoteAddress->method('getRemoteAddress')->willReturn('104.40.179.234');
        $this->_objectManager->addSharedInstance($mockRemoteAddress, \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class);

        //minimum params required to create kick start callback url to create trial
        $params = [
            'apiUser' => $apiUser,
            'pass' => $apiPass
        ];
        $this->getRequest()->setParams($params);

        $this->dispatch('connector/email/accountcallback');

        $this->assertContains(
            'Congratulations your dotmailer account is now ready',
            $this->getResponse()->getBody(),
            'Trial Account creation failed'
        );
    }

    /**
     * provide api credentials here for testing
     *
     * @return array
     */
    public function apiDetailsDataProvider()
    {
        //Enter real api username and password before running this test
        return [
            [
                'ENTER-API-USERNAME',
                'ENTER-API-PASSWORD'
            ]
        ];
    }
}