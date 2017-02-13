<?php

namespace Dotdigitalgroup\Email\Model\Trial;

/**
 * Class TrailAccountSetupStepsTest
 * @package Dotdigitalgroup\Email\Controller\Email
 * @magentoDBIsolation enabled
 */
class TrailAccountSetupStepsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Object Manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;
    /**
     * @var \Dotdigitalgroup\Email\Model\Trial\TrialSetup
     */
    public $trialSetup;

    public function setup()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->trialSetup = $this->objectManager->get('Dotdigitalgroup\Email\Model\Trial\TrialSetup');
    }

    /**
     * @param $apiUser
     * @param $apiPass
     * @dataProvider apiDetailsDataProvider
     */
    public function test_save_api_creds($apiUser, $apiPass)
    {
        $this->assertTrue(
            $this->trialSetup->saveApiCreds($apiUser, $apiPass),
            'api creds not saved'
        );
    }

    /**
     * @param $apiUser
     * @param $apiPass
     * @dataProvider apiDetailsDataProvider
     */
    public function test_setup_data_fields($apiUser, $apiPass)
    {
        $this->assertTrue(
            $this->trialSetup->setupDataFields($apiUser, $apiPass),
            'data field creation and mapping returned error'
        );
    }

    /**
     * @param $apiUser
     * @param $apiPass
     * @dataProvider apiDetailsDataProvider
     */
    public function test_create_address_books($apiUser, $apiPass)
    {
        $this->assertTrue(
            $this->trialSetup->createAddressBooks($apiUser, $apiPass),
            'Address books creation and mapping returned error'
        );
    }

    public function test_enable_syncs_for_trial()
    {
        $this->assertTrue($this->trialSetup->enableSyncForTrial());
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