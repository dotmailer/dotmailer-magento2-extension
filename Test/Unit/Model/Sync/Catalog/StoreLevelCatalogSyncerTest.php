<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog as CatalogResource;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog;
use Dotdigitalgroup\Email\Model\Sync\Catalog\StoreCatalogSyncer;
use Dotdigitalgroup\Email\Model\Sync\Catalog\StoreLevelCatalogSyncer;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\App\Emulation;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @var CatalogFactory
     */
    private $catalogResourceFactoryMock;

    /**
     * @var Catalog|MockObject
     */
    private $catalogMock;

    /**
     * @var StoreCatalogSyncer
     */
    private $storeCatalogSyncerMock;

    /**
     * @var CatalogResource
     */
    private $catalogResourceMock;

    /**
     * @var WebsiteInterface;
     */
    private $websiteInterfaceMock;

    /**
     * @var Emulation
     */
    private $appEmulation;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->catalogMock = $this->createMock(Catalog::class);
        $this->catalogResourceFactoryMock = $this->createMock(CatalogFactory::class);
        $this->storeCatalogSyncerMock = $this->createMock(StoreCatalogSyncer::class);
        $this->catalogResourceMock = $this->createMock(CatalogResource::class);
        $this->websiteInterfaceMock = $this->createMock(WebsiteInterface::class);
        $this->appEmulation = $this->createMock(Emulation::class);
        $this->storeLevelCatalogSyncer = new StoreLevelCatalogSyncer(
            $this->helperMock,
            $this->storeCatalogSyncerMock,
            $this->appEmulation,
            $this->catalogMock
        );
    }

    /**
     * @dataProvider getProducts
     * @param $products
     */
    public function testNumberOfProductsIfBothAreEnabled($products)
    {
        $store1 = $this->getMockedStoresEnabled();
        $store2 = $this->getMockedStoresEnabled();

        $expected = 2;

        $stores = [$store1['store'], $store2['store']];
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

        $this->storeCatalogSyncerMock->expects($this->exactly(2))
            ->method('syncByStore')
            ->willReturnOnConsecutiveCalls(
                [0 =>'product1'],
                [1 => 'product2']
            );

        $this->catalogMock->expects($this->exactly(2))
            ->method('getStoreCatalogName');

        $result = $this->storeLevelCatalogSyncer->sync($products);
        $this->assertEquals(count($result), $expected);
    }

    /**
     * @dataProvider getProducts
     * @param $products
     */
    public function testNumberOfProductsIfOnlyOneEnabled($products)
    {
        $store1 = $this->getMockedStoresEnabled();
        $store2 = $this->getMockedStoresDisabled();

        $expected = 1;

        $stores = [$store1['store'], $store2['store']];
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

        $this->storeCatalogSyncerMock->expects($this->once())
            ->method('syncByStore')
            ->willReturn([0 =>'product1']);

        $this->catalogMock->expects($this->once())
            ->method('getStoreCatalogName');

        $result = $this->storeLevelCatalogSyncer->sync($products);

        $this->assertEquals(count($result), $expected);
    }

    private function getMockedStoresEnabled()
    {
        $storeDetails = $this->getStoreDetails();
        $store = $this->createMock(StoreInterface::class);

        $store->expects($this->exactly(3))
            ->method('getWebsiteId')
            ->willReturn($storeDetails['websiteId']);
        $store->expects($this->once())
            ->method('getId')
            ->willReturn($storeDetails['storeId']);

        return [
            'store' => $store,
            'details' => $storeDetails
        ];
    }

    /**
     * @dataProvider getProducts
     * @param $products
     */
    public function testNumberOfProductsIfNoOneEnabled($products)
    {
        $store1 = $this->getMockedStoresDisabled();
        $store2 = $this->getMockedStoresDisabled();

        $expected = 0;

        $stores = [$store1['store'], $store2['store']];
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

        $this->storeCatalogSyncerMock->expects($this->never())
            ->method('syncByStore');

        $this->catalogMock->expects($this->never())
            ->method('getStoreCatalogName');

        $result = $this->storeLevelCatalogSyncer->sync($products);
        $this->assertEquals(count($result), $expected);
    }

    /**
     * @dataProvider getProducts
     * @param $products
     */
    public function testAppEmulationIsUsedIfSyncEnabled($products)
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

        $this->storeCatalogSyncerMock->expects($this->exactly(2))
            ->method('syncByStore')
            ->willReturnOnConsecutiveCalls(
                [0 =>'product1'],
                [1 => 'product2']
            );

        $this->storeLevelCatalogSyncer->sync($products);
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
            'code' => hash("sha256", rand())
        ];
    }

    /**
     * Returns possible array product combinations
     * @return array
     */
    public function getProducts()
    {
        return [
            [['Product 1','Product 2','Product 3']],
            [['Product A','Product B','Product C','Product D']],
            [['Product a','Product b']],
            [['Product X','Product Y','Product Z','Product W']]
        ];
    }
}
