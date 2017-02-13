<?php

namespace Dotdigitalgroup\Email\Controller\Email;


use Magento\TestFramework\ObjectManager;

/**
 * Class TrailAccountCreationTest
 * @package Dotdigitalgroup\Email\Controller\Email
 * @magentoDBIsolation enabled
 */
class TrialAccountCreationTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * Object Manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @param $apiUser
     * @param $apiPass
     * @dataProvider apiDetailsDataProvider
     */
    public function test_trial_account_created_successfully($apiUser, $apiPass)
    {
        /** @var ObjectManager $objectManager */
        $objectManager = ObjectManager::getInstance();

        $mockRemoteAddress = $this->getMock('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress', [], [], '', false);
        $mockRemoteAddress->method('getRemoteAddress')->willReturn('104.40.179.234');
        $objectManager->addSharedInstance($mockRemoteAddress, \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class);

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
            'Trial Account creation faileds'
        );
    }

    /**
     * provide api credentials here for testing
     *
     * @return array
     */
    public function apiDetailsDataProvider()
    {
        return [
            [
                'apiuser-debb7563798a@apiconnector.com',
                'Magento2015!!'
            ]
        ];
    }
}