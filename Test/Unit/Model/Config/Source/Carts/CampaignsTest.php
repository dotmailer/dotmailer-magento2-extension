<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Config\Source\Carts;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Config\Source\Carts\Campaigns;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CampaignsTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $helper;

    /**
     * @var MockObject
     */
    private $registry;

    /**
     * @var WebsiteInterface|MockObject
     */
    private $websiteInterfaceMock;

    /**
     * Mock class dependencies
     */
    protected function setUp() :void
    {
        $this->helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteInterfaceMock = $this->createMock(WebsiteInterface::class);
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
            array_map(function ($field) {
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

        $this->helper->expects($this->atLeastOnce())
            ->method('getWebsiteForSelectedScopeInAdmin')
            ->willReturn($this->websiteInterfaceMock);

        $this->websiteInterfaceMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($websiteId);

        $this->helper->expects($this->once())
            ->method('isEnabled')
            ->with($this->websiteInterfaceMock)
            ->willReturn(true);

        $this->registry->expects($this->once())
            ->method('registry')
            ->with('campaigns')
            ->willReturn(null);

        $clientMock = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        // mock getCampaigns calls depending on the number of API responses
        $apiResponseChunks = ceil(count($testApiResponse) / 1000);
        $clientMock->expects($this->exactly($apiResponseChunks))
            ->method('getCampaigns')
            ->withConsecutive(
                [0],
                [1000]
            )
            ->willReturnOnConsecutiveCalls(
                array_slice($testApiResponse, 0, 1000),
                array_slice($testApiResponse, 1000, 1000)
            );

        $this->helper->expects($this->once())
            ->method('getWebsiteApiClient')
            ->with($websiteId)
            ->willReturn($clientMock);

        $this->registry->expects($this->once())
            ->method('register')
            ->with('campaigns', $testApiResponse);

        $campaigns = new Campaigns($this->registry, $this->helper);
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
        return array_map(function ($number) {
            return (object) [
                'id' => $number,
                'name' => 'campaign' . $number,
            ];
        }, range(0, $numberOfResponses));
    }
}
