<?php

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog\StoreCatalogSyncer;
use Dotdigitalgroup\Email\Model\Sync\Catalog\StoreLevelCatalogSyncer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\App\Emulation;
use PHPUnit\Framework\TestCase;

class StoreLevelCatalogSyncerTest extends TestCase
{
    /**
     * @var StoreLevelCatalogSyncer
     */
    private $storeLevelCatalogSyncer;

    /**
     * @var Data
     */
    private $helperMock;

    /**
     * @var ScopeConfigInterface;
     */
    private $scopeConfigMock;

    /**
     * @var CatalogFactory
     */
    private $catalogResourceFactoryMock;

    /**
     * @var StoreCatalogSyncer
     */
    private $storeCatalogSyncerMock;

    /**
     * @var Catalog
     */
    private $catalogResourceMock;

    /**
     * @var WebsiteInterface;
     */
    private $webSiteInterfaceMock;

    /**
     * @var Emulation
     */
    private $appEmulation;

    protected function setUp()
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->catalogResourceFactoryMock = $this->createMock(CatalogFactory::class);
        $this->storeCatalogSyncerMock = $this->createMock(StoreCatalogSyncer::class);
        $this->catalogResourceMock = $this->createMock(Catalog::class);
        $this->webSiteInterfaceMock = $this->createMock(WebsiteInterface::class);
        $this->appEmulation = $this->createMock(Emulation::class);
        $this->storeLevelCatalogSyncer = new StoreLevelCatalogSyncer(
            $this->helperMock,
            $this->scopeConfigMock,
            $this->catalogResourceMock,
            $this->storeCatalogSyncerMock,
            $this->appEmulation
        );
    }

    public function testNumberOfProductsIfBothAreEnabled()
    {
        $store1 = $this->getMockedStoresEnabled();
        $store2 = $this->getMockedStoresEnabled();

        $expected = 2;

        $stores = [$store1['store'],$store2['store']];
        $this->helperMock->expects($this->once())
            ->method('getStores')
            ->willReturn($stores);

        $this->helperMock->method('isEnabled')
            ->withConsecutive(
                [$store1['details']['websiteId']],
                [$store2['details']['websiteId']]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->helperMock->method('isCatalogSyncEnabled')
            ->withConsecutive(
                [$store1['details']['websiteId']],
                [$store2['details']['websiteId']]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->scopeConfigMock->expects($this->exactly(1))
            ->method('getValue');

        $this->storeCatalogSyncerMock->expects($this->at(0))
            ->method('syncByStore')
            ->willReturn([0 =>'product1']);

        $this->storeCatalogSyncerMock->expects($this->at(1))
            ->method('syncByStore')
            ->willReturn([1 => 'product2']);

        $this->catalogResourceMock->expects($this->exactly(1))
            ->method('setImportedByIds');

        $this->webSiteInterfaceMock->expects($this->exactly(2))
            ->method('getCode')
            ->willReturn(md5(rand()));

        $result = $this->storeLevelCatalogSyncer->sync();
        $this->assertEquals($result, $expected);
    }

    public function testNumberOfProductsIfOnlyOneEnabled()
    {
        $store1 = $this->getMockedStoresEnabled();
        $store2 = $this->getMockedStoresDisabled();

        $expected = 1;

        $stores = [$store1['store'],$store2['store']];
        $this->helperMock->expects($this->once())
            ->method('getStores')
            ->willReturn($stores);

        $this->helperMock->method('isEnabled')
            ->withConsecutive(
                [$store1['details']['websiteId']],
                [$store2['details']['websiteId']]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $this->helperMock->method('isCatalogSyncEnabled')
            ->withConsecutive(
                [$store1['details']['websiteId']],
                [$store2['details']['websiteId']]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $this->scopeConfigMock->expects($this->exactly(1))
            ->method('getValue');

        $this->storeCatalogSyncerMock->expects($this->at(0))
            ->method('syncByStore')
            ->willReturn([0 =>'product1']);

        $this->catalogResourceMock->expects($this->exactly(1))
            ->method('setImportedByIds');

        $this->webSiteInterfaceMock->expects($this->at(0))
            ->method('getCode')
            ->willReturn(md5(rand()));

        $result = $this->storeLevelCatalogSyncer->sync();
        $this->assertEquals($result, $expected);
    }

    private function getMockedStoresEnabled()
    {
        $storeDetails = $this->getStoreDetails();
        $store = $this->getMockBuilder(StoreInterface::class)
                        ->setMethods(['getWebsite'])
                        ->getMockForAbstractClass();
        $store->expects($this->exactly(3))
            ->method('getWebsiteId')
            ->willReturn($storeDetails['websiteId']);
        $store->expects($this->once())
            ->method('getId')
            ->willReturn($storeDetails['storeId']);
        $store->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturn($storeDetails['code']);
        $store->expects($this->exactly(1))
            ->method('getWebsite')
            ->willReturn($this->webSiteInterfaceMock);

        return [
            'store' => $store,
            'details' => $storeDetails
        ];
    }

    public function testNumberOfProductsIfNoOneEnabled()
    {
        $store1 = $this->getMockedStoresDisabled();
        $store2 = $this->getMockedStoresDisabled();

        $expected = 0;

        $stores = [$store1['store'],$store2['store']];
        $this->helperMock->expects($this->once())
            ->method('getStores')
            ->willReturn($stores);

        $this->helperMock->method('isEnabled')
            ->withConsecutive(
                [$store1['details']['websiteId']],
                [$store2['details']['websiteId']]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false
            );

        $this->helperMock->method('isCatalogSyncEnabled')
            ->withConsecutive(
                [$store1['details']['websiteId']],
                [$store2['details']['websiteId']]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false
            );

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue');

        $this->storeCatalogSyncerMock->expects($this->never())
            ->method('syncByStore');

        $this->catalogResourceMock->expects($this->exactly(1))
            ->method('setImportedByIds');

        $this->webSiteInterfaceMock->expects($this->never())
            ->method('getCode')
            ->willReturn(md5(rand()));

        $result = $this->storeLevelCatalogSyncer->sync();
        $this->assertEquals($result, $expected);
    }

    public function testAppEmulationIsUsedIfSyncEnabled()
    {
        $store1 = $this->getMockedStoresEnabled();
        $store2 = $this->getMockedStoresEnabled();

        $this->helperMock->expects($this->once())
            ->method('getStores')
            ->willReturn([$store1['store'],$store2['store']]);

        $this->appEmulation->expects($this->exactly(2))
            ->method('startEnvironmentEmulation')
            ->withConsecutive(
                [$store1['details']['storeId']],
                [$store2['details']['storeId']]
            );

        $this->appEmulation->expects($this->exactly(2))
            ->method('stopEnvironmentEmulation');

        $this->helperMock->method('isEnabled')
            ->withConsecutive(
                [$store1['details']['websiteId']],
                [$store2['details']['websiteId']]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->helperMock->method('isCatalogSyncEnabled')
            ->withConsecutive(
                [$store1['details']['websiteId']],
                [$store2['details']['websiteId']]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->storeCatalogSyncerMock->expects($this->at(0))
            ->method('syncByStore')
            ->willReturn([0 =>'product1']);

        $this->storeCatalogSyncerMock->expects($this->at(1))
            ->method('syncByStore')
            ->willReturn([1 => 'product2']);

        $this->storeLevelCatalogSyncer->sync();
    }

    /**
     * Generates the disabled stores to be mocked
     * @return array
     */
    private function getMockedStoresDisabled()
    {
        $storeDetails = $this->getStoreDetails();
        $store = $this->getMockBuilder(StoreInterface::class)
            ->setMethods(['getWebsite'])
            ->getMockForAbstractClass();
        $store->expects($this->exactly(2))
            ->method('getWebsiteId')
            ->willReturn($storeDetails['websiteId']);
        $store->expects($this->never())
            ->method('getId');
        $store->expects($this->never())
            ->method('getCode');
        $store->expects($this->never())
            ->method('getWebsite');

        return [
            'store' => $store,
            'details' => $storeDetails
        ];
    }

    /**
     * Initializes the Enabled Mocked Stores
     * @return array
     */
    public function getStoreDetails()
    {
        return [
          'websiteId' => rand(1, 10),
          'storeId' => rand(1, 10),
          'code' => md5(rand())
        ];
    }
}
