<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Config\Source\Carts;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Config\Source\Carts\Campaigns;
use Magento\Framework\Escaper;
use Magento\Framework\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CampaignsTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $escaper;

    /**
     * @var MockObject
     */
    private $helper;

    /**
     * @var MockObject
     */
    private $registry;

    /**
     * Mock class dependencies
     */
    protected function setUp()
    {
        $this->escaper = $this->getMockBuilder(Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test getAllCampaigns with < 1000 campaigns in Engagement Cloud
     */
    public function testGetAllCampaignsUnderOneThousand()
    {
        $this->getCampaignsTest($this->getTestApiResponse(20));
    }

    /**
     * Test getAllCampaigns with > 1000 campaigns in Engagement Cloud
     */
    public function testGetAllCampaignsOverOneThousand()
    {
        $this->getCampaignsTest($this->getTestApiResponse(1200));
    }

    /**
     * Check that fields returned by the method match the expected format
     */
    public function testFieldsResponse()
    {
        $testApiResponse = $this->getTestApiResponse(1001);
        $fields = $this->getCampaignsTest($testApiResponse);

        // check fields returned matches the initial option + the array of values/labels generated
        $expectedResponse = array_merge(
            [[
                'value' => '0',
                'label' => '-- Please Select --',
            ]],
            array_map(function($field) {
                return [
                    'value' => $field->id,
                    'label' => $field->name,
                ];
            }, $testApiResponse)
        );
        $this->assertEquals($expectedResponse, $fields);
    }

    /**
     * Perform getAllCampaigns tests
     *
     * @param array $testApiResponse    The API response
     * @return array
     */
    private function getCampaignsTest(array $testApiResponse)
    {
        $websiteId = 1234;
        $this->helper->expects($this->exactly(2))
            ->method('getWebsiteForSelectedScopeInAdmin')
            ->willReturn($websiteId);

        $this->helper->expects($this->once())
            ->method('isEnabled')
            ->with($websiteId)
            ->willReturn(true);

        $this->registry->expects($this->once())
            ->method('registry')
            ->with('campaigns')
            ->willReturn(null);

        $clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        // mock getCampaigns calls depending on the number of API responses
        for ($r = 0; $r < ceil(count($testApiResponse) / 1000); $r++) {
            $clientMock->expects($this->at($r))
                ->method('getCampaigns')
                ->with($r * 1000)
                ->willReturn(array_slice($testApiResponse, $r * 1000, 1000));
        }

        $this->helper->expects($this->once())
            ->method('getWebsiteApiClient')
            ->with($websiteId)
            ->willReturn($clientMock);

        $this->registry->expects($this->once())
            ->method('register')
            ->with('campaigns', $testApiResponse);

        $campaigns = new Campaigns($this->registry, $this->helper, $this->escaper);
        return $campaigns->toOptionArray();
    }

    /**
     * Get a test API response
     *
     * @param $numberOfResponses    Number of campaigns returned by API
     * @return array
     */
    private function getTestApiResponse($numberOfResponses)
    {
        // example API response with > 1000 campaigns
        return array_map(function($number) {
            return (object) [
                'id' => $number,
                'name' => 'campaign' . $number,
            ];
        }, range(0, $numberOfResponses));
    }
}