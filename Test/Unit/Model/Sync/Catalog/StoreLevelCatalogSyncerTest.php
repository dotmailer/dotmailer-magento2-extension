<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Sync\Catalog;
use Dotdigitalgroup\Email\Model\Sync\Catalog\StoreCatalogSyncer;
use Dotdigitalgroup\Email\Model\Sync\Catalog\StoreLevelCatalogSyncer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
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
     * @var Catalog|MockObject
     */
    private $catalogMock;

    /**
     * @var StoreCatalogSyncer
     */
    private $storeCatalogSyncerMock;

    /**
     * @var StoreManagerInterface
     */
    private $storeManagerMock;

    /**
     * @var Emulation
     */
    private $appEmulation;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->catalogMock = $this->createMock(Catalog::class);
        $this->storeCatalogSyncerMock = $this->createMock(StoreCatalogSyncer::class);
        $this->appEmulation = $this->createMock(Emulation::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->storeLevelCatalogSyncer = new StoreLevelCatalogSyncer(
            $this->helperMock,
            $this->storeCatalogSyncerMock,
            $this->appEmulation,
            $this->catalogMock,
            $this->storeManagerMock
        );
    }

    /**
     * @dataProvider getProducts
     *
     * @param        $products
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testNumberOfProductsIfBothAreEnabled($products)
    {
        $store1 = $this->getMockedStoresEnabled();
        $store2 = $this->getMockedStoresEnabled();

        $expected = 2;

        $stores = [$store1['store'], $store2['store']];
        $this->storeManagerMock->expects($this->once())
            ->method('getStores')
            ->willReturn($stores);

        $this->helperMock->expects($this->exactly(2))
            ->method('isEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->exactly(2))
            ->method('isCatalogSyncEnabled')
            ->willReturn(true);

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
     *
     * @param        $products
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testNumberOfProductsIfOnlyOneEnabled($products)
    {
        $store1 = $this->getMockedStoresEnabled();
        $store2 = $this->getMockedStoresDisabled();
        $matcher = $this->exactly(2);
        $expected = 1;

        $stores = [$store1['store'], $store2['store']];
        $this->storeManagerMock->expects($this->once())
            ->method('getStores')
            ->willReturn($stores);

        $this->helperMock->method('isEnabled')
            ->willReturnCallback(function () use ($matcher, $store1, $store2) {
                return match ($matcher->getInvocationCount()) {
                    1 => [$store1['details']['websiteId']],
                    2 => [$store2['details']['websiteId']]
                };
            })
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $this->helperMock->method('isCatalogSyncEnabled')
            ->willReturnCallback(function () use ($matcher, $store1, $store2) {
                return match ($matcher->getInvocationCount()) {
                    1 => [$store1['details']['websiteId']],
                    2 => [$store2['details']['websiteId']]
                };
            })
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

    /**
     * @dataProvider getProducts
     *
     * @param        $products
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testNumberOfProductsIfNoStoreEnabled($products)
    {
        $store1 = $this->getMockedStoresDisabled();
        $store2 = $this->getMockedStoresIsActiveFalse();
        $matcher = $this->exactly(2);
        $expected = 0;

        $stores = [$store1['store'], $store2['store']];
        $this->storeManagerMock->expects($this->once())
            ->method('getStores')
            ->willReturn($stores);

        $this->helperMock->method('isEnabled')
            ->willReturnCallback(function () use ($matcher, $store1, $store2) {
                return match ($matcher->getInvocationCount()) {
                    1 => [$store1['details']['websiteId']],
                    2 => [$store2['details']['websiteId']]
                };
            })
            ->willReturnOnConsecutiveCalls(
                false,
                true
            );

        $this->helperMock->method('isCatalogSyncEnabled')
            ->willReturnCallback(function () use ($matcher, $store1, $store2) {
                return match ($matcher->getInvocationCount()) {
                    1 => [$store1['details']['websiteId']],
                    2 => [$store2['details']['websiteId']]
                };
            })
            ->willReturnOnConsecutiveCalls(
                false,
                true
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
     *
     * @param        $products
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function testAppEmulationIsUsedIfSyncEnabled($products)
    {
        $store1 = $this->getMockedStoresEnabled();
        $store2 = $this->getMockedStoresEnabled();

        $this->storeManagerMock->expects($this->once())
            ->method('getStores')
            ->willReturn([$store1['store'],$store2['store']]);

        $this->appEmulation->expects($this->exactly(2))
            ->method('startEnvironmentEmulation');

        $this->appEmulation->expects($this->exactly(2))
            ->method('stopEnvironmentEmulation');

        $this->helperMock->expects($this->exactly(2))
            ->method('isEnabled')
            ->willReturn(true);

        $this->helperMock->expects($this->exactly(2))
            ->method('isCatalogSyncEnabled')
            ->willReturn(true);

        $matcher = $this->exactly(2);
        $this->storeCatalogSyncerMock->expects($this->exactly(2))
            ->method('syncByStore')
            ->willReturnCallback(function () use ($matcher) {
                return match ($matcher->getInvocationCount()) {
                    1 => [['product1'], 1, 1, 'my_catalog'],
                    2 => [['product2'], 1, 1, 'my_catalog']
                };
            })
            ->willReturnOnConsecutiveCalls(
                [0 => 'product1'],
                [1 => 'product2']
            );

        $this->storeLevelCatalogSyncer->sync($products);
    }

    private function getMockedStoresEnabled()
    {
        $storeDetails = $this->getStoreDetails();
        $store = $this->createMock(Store::class);

        $store->expects($this->exactly(3))
            ->method('getWebsiteId')
            ->willReturn($storeDetails['websiteId']);
        $store->expects($this->once())
            ->method('getId')
            ->willReturn($storeDetails['storeId']);
        $store->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        return [
            'store' => $store,
            'details' => $storeDetails
        ];
    }

    /**
     * Generates the disabled stores to be mocked
     *
     * @return array
     */
    private function getMockedStoresDisabled()
    {
        $storeDetails = $this->getStoreDetails();
        $store = $this->createMock(Store::class);

        $store->expects($this->exactly(2))
            ->method('getWebsiteId')
            ->willReturn($storeDetails['websiteId']);
        $store->expects($this->never())
            ->method('getId');
        $store->expects($this->once())
            ->method('isActive')
            ->willReturn(true);

        return [
            'store' => $store,
            'details' => $storeDetails
        ];
    }

    /**
     * Generates the disabled stores to be mocked
     *
     * @return array
     */
    private function getMockedStoresIsActiveFalse()
    {
        $storeDetails = $this->getStoreDetails();
        $store = $this->createMock(Store::class);

        $store->expects($this->exactly(2))
            ->method('getWebsiteId')
            ->willReturn($storeDetails['websiteId']);
        $store->expects($this->never())
            ->method('getId');
        $store->expects($this->once())
            ->method('isActive')
            ->willReturn(false);

        return [
            'store' => $store,
            'details' => $storeDetails
        ];
    }

    /**
     * Initializes the Enabled Mocked Stores
     *
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
     *
     * @return array
     */
    public static function getProducts()
    {
        return [
            [['Product 1','Product 2','Product 3']],
            [['Product A','Product B','Product C','Product D']],
            [['Product a','Product b']],
            [['Product X','Product Y','Product Z','Product W']]
        ];
    }
}
