<?php

namespace Dotdigitalgroup\Email\Model;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\TestFramework\ObjectManager;

class IntegrationInsightDataTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

    const API_USER_HASH = '53acb5d20576';

    private static $apiUser = "apiuser-" . self::API_USER_HASH . "@apiconnector.com";

    /**
     * @var IntegrationInsightData
     */
    private $integrationInsightData;

    public function setUp()
    {
        $this->setApiConfigFlags([
            Config::XML_PATH_CONNECTOR_API_USERNAME => self::$apiUser,
        ]);
        $this->integrationInsightData = ObjectManager::getInstance()->create(IntegrationInsightData::class);
    }

    public function testInsightDataFields()
    {
        $data = $this->integrationInsightData->getIntegrationInsightData()[0];

        $this->assertArraySubset([
            'recordId',
            'websites',
            'apiUsername',
            'lastUpdated',
            'platform',
            'edition',
            'version',
            'connectorVersion',
        ], array_keys($data));
    }

    public function testApiUser()
    {
        $data = $this->integrationInsightData->getIntegrationInsightData()[0];

        $this->assertEquals(sprintf('integration_%s', self::API_USER_HASH), $data['recordId']);
        $this->assertEquals(self::$apiUser, $data['apiUsername']);
    }
}
