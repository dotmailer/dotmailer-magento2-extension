<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Sync\Integration\DotdigitalConfig;
use Dotdigitalgroup\Email\Model\Sync\Integration\IntegrationInsightData;
use Dotdigitalgroup\Email\Model\Connector\Module;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IntegrationInsightDataUnitTest extends TestCase
{
    private const PLATFORM = 'Magento';
    private const EDITION = 'Community';
    private const VERSION = '2.3';
    private const CONNECTOR_VERSION = '3.4.0';

    /**
     * @var IntegrationInsightData
     */
    private $integrationInsightData;

    /**
     * @var Data
     */
    private $helperMock;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadataMock;

    /**
     * @var \Dotdigitalgroup\Email\Model\Sync\Integration\DotdigitalConfig|MockObject
     */
    private $dotdigitalConfigMock;

    /**
     * @var StoreManagerInterface|mixed|MockObject
     */
    private $storeManagerInterfaceMock;

    /**
     * @var Module|Module&MockObject|MockObject
     */
    private $moduleManagerMock;

    /**
     * @var Store|Store&MockObject|MockObject
     */
    private $storeMock;

    /**
     * @var Website|MockObject
     */
    private $websiteMock;

    public function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->productMetadataMock = $this->createMock(ProductMetadataInterface::class);
        $this->dotdigitalConfigMock = $this->createMock(DotdigitalConfig::class);
        $this->storeManagerInterfaceMock = $this->createMock(StoreManagerInterface::class);
        $this->moduleManagerMock = $this->createMock(Module::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->websiteMock = $this->createMock(Website::class);

        // set up metadata
        $this->productMetadataMock
            ->expects($this->once())
            ->method('getName')
            ->willReturn(self::PLATFORM);
        $this->productMetadataMock
            ->expects($this->once())
            ->method('getEdition')
            ->willReturn(self::EDITION);
        $this->productMetadataMock
            ->expects($this->once())
            ->method('getVersion')
            ->willReturn(self::VERSION);
        $this->moduleManagerMock
            ->expects($this->once())
            ->method('getModuleVersion')
            ->with('Dotdigitalgroup_Email')
            ->willReturn(
                [
                'setup_version' => self::CONNECTOR_VERSION,
                ]
            );

        $this->storeManagerInterfaceMock->expects($this->once())
            ->method('getStores')
            ->willReturn(
                [
                    $this->storeMock,
                    $this->storeMock,
                    $this->storeMock
                ]
            );

        $this->storeMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturnOnConsecutiveCalls(1, 1, 1, 1, 2, 2, 2, 2, 3, 3, 3, 3);

        $this->storeMock->expects($this->atLeastOnce())
            ->method('getWebsite')
            ->willReturn($this->websiteMock);

        $this->websiteMock
            ->method('getName')
            ->willReturnOnConsecutiveCalls('Default', 'Typos', 'Bye Bye Man');

        $this->websiteMock->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturnOnConsecutiveCalls('chaz', 'wingman', 'sprat');

        $this->storeMock->expects($this->atLeastOnce())
            ->method('getBaseUrl')
            ->willReturnOnConsecutiveCalls(
                'https://www.chaz-kangaroo.com',
                'https://www.chaz-kangaroo.com/cauals',
                'https://www.chaz-kangaroo.com'
            );

        $this->storeMock->expects($this->atLeastOnce())
            ->method('isCurrentlySecure')
            ->willReturnOnConsecutiveCalls(true, true, false);

        $this->integrationInsightData = new IntegrationInsightData(
            $this->helperMock,
            $this->productMetadataMock,
            $this->dotdigitalConfigMock,
            $this->storeManagerInterfaceMock,
            $this->moduleManagerMock
        );
    }

    /**
     * Test expected integration records are returned
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testIntegrationData()
    {
        // set one website to have API disabled
        $enabledCheck = 0;
        $this->helperMock
            ->expects($this->any())
            ->method('isEnabled')
            ->will(
                $this->returnCallback(
                    function () use (&$enabledCheck) {
                        if ($enabledCheck++ === 1) {
                            return false;
                        }
                        return true;
                    }
                )
            );

        $data = $this->integrationInsightData->getIntegrationInsightData();

        // assert 2 records were returned, with separate integration IDs based on the API hash
        $this->assertCount(2, $data);
        $this->assertEquals('www.chaz-kangaroo.com?website_code=chaz', reset($data)['recordId']);
        $this->assertEquals('www.chaz-kangaroo.com?website_code=wingman', end($data)['recordId']);
    }

    /**
     * Test expected metadata is returned
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function testMetadataReturned()
    {
        $this->helperMock
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $data = $this->integrationInsightData->getIntegrationInsightData();
        $toAssert = reset($data);

        $this->assertArrayHasKey('platform', $toAssert);
        $this->assertArrayHasKey('edition', $toAssert);
        $this->assertArrayHasKey('version', $toAssert);
        $this->assertArrayHasKey('connectorVersion', $toAssert);

        $this->assertArrayHasKey('phpVersion', $toAssert);
        $this->assertArrayHasKey('configuration', $toAssert);
    }
}
