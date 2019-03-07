<?php

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\ImporterFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog;
use Dotdigitalgroup\Email\Model\ResourceModel\CatalogFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog\StoreCatalogSyncer;
use Dotdigitalgroup\Email\Model\Sync\Catalog\StoreLevelCatalogSyncer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use PHPUnit\Framework\TestCase;

class StoreLevelCatalogSyncerTest extends TestCase
{
    /**
     * @var StoreLevelCatalogSyncer
     */
    private $storeLevelCatalogSyncer;

    /**
     * @var ImporterFactory
     */
    private $importerFactoryMock;

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

    protected function setUp()
    {
        $this->importerFactoryMock = $this->createMock(ImporterFactory::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->catalogResourceFactoryMock = $this->createMock(CatalogFactory::class);
        $this->storeCatalogSyncerMock = $this->createMock(StoreCatalogSyncer::class);
        $this->catalogResourceMock = $this->createMock(Catalog::class);
        $this->webSiteInterfaceMock = $this->createMock(WebsiteInterface::class);
        $this->storeLevelCatalogSyncer = new StoreLevelCatalogSyncer(
            $this->importerFactoryMock,
            $this->helperMock,
            $this->scopeConfigMock,
            $this->catalogResourceMock,
            $this->storeCatalogSyncerMock
        );
    }

    public function testNumberOfProductsIfBothAreEnabled()
    {
        $product1 = $this->getMockedProductsEnabled();
        $product2 = $this->getMockedProductsEnabled();

        $expected = 2;

        $products = [$product1['product'],$product2['product']];
        $this->helperMock->expects($this->once())
            ->method('getStores')
            ->willReturn($products);

        $this->helperMock->method('isEnabled')
            ->withConsecutive(
                [$product1['details']['websiteId']],
                [$product2['details']['websiteId']]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true
            );

        $this->helperMock->method('isCatalogSyncEnabled')
            ->withConsecutive(
                [$product1['details']['websiteId']],
                [$product2['details']['websiteId']]
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
        $product1 = $this->getMockedProductsEnabled();
        $product2 = $this->getMockedProductsDisabled();

        $expected = 1;

        $products = [$product1['product'],$product2['product']];
        $this->helperMock->expects($this->once())
            ->method('getStores')
            ->willReturn($products);

        $this->helperMock->method('isEnabled')
            ->withConsecutive(
                [$product1['details']['websiteId']],
                [$product2['details']['websiteId']]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );

        $this->helperMock->method('isCatalogSyncEnabled')
            ->withConsecutive(
                [$product1['details']['websiteId']],
                [$product2['details']['websiteId']]
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

    private function getMockedProductsEnabled()
    {
        $productDetails = $this->getProductDetails();
        $product = $this->getMockBuilder(StoreInterface::class)
                        ->setMethods(['getWebsite'])
                        ->getMockForAbstractClass();
        $product->expects($this->exactly(3))
            ->method('getWebsiteId')
            ->willReturn($productDetails['websiteId']);
        $product->expects($this->once())
            ->method('getId')
            ->willReturn($productDetails['productId']);
        $product->expects($this->atLeastOnce())
            ->method('getCode')
            ->willReturn($productDetails['code']);
        $product->expects($this->exactly(1))
            ->method('getWebsite')
            ->willReturn($this->webSiteInterfaceMock);

        return [
            'product' => $product,
            'details' => $productDetails
        ];
    }

    public function testNumberOfProductsIfNoOneEnabled()
    {
        $product1 = $this->getMockedProductsDisabled();
        $product2 = $this->getMockedProductsDisabled();

        $expected = 0;

        $products = [$product1['product'],$product2['product']];
        $this->helperMock->expects($this->once())
            ->method('getStores')
            ->willReturn($products);

        $this->helperMock->method('isEnabled')
            ->withConsecutive(
                [$product1['details']['websiteId']],
                [$product2['details']['websiteId']]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false
            );

        $this->helperMock->method('isCatalogSyncEnabled')
            ->withConsecutive(
                [$product1['details']['websiteId']],
                [$product2['details']['websiteId']]
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

    /**
     * Generates the Disabled Products to be mocked
     * @return array
     */
    private function getMockedProductsDisabled()
    {
        $productDetails = $this->getProductDetails();
        $product = $this->getMockBuilder(StoreInterface::class)
            ->setMethods(['getWebsite'])
            ->getMockForAbstractClass();
        $product->expects($this->exactly(2))
            ->method('getWebsiteId')
            ->willReturn($productDetails['websiteId']);
        $product->expects($this->never())
            ->method('getId');
        $product->expects($this->never())
            ->method('getCode');
        $product->expects($this->never())
            ->method('getWebsite');

        return [
            'product' => $product,
            'details' => $productDetails
        ];
    }

    /**
     * Initializes the Enabled Mocked Products
     * @return array
     */
    public function getProductDetails()
    {
        return [
          'websiteId' => rand(1, 10),
          'productId' => rand(1, 10),
          'code' => md5(rand())
        ];
    }
}
