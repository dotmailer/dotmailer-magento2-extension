<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Catalog;

use Dotdigital\V3\Models\InsightData\RecordsCollection as RecordsCollectionAlias;
use Dotdigitalgroup\Email\Model\Connector\ProductFactory as ConnectorProductFactory;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Sync\Catalog\Exporter;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkCatalogRecordCollectionBuilder;
use Dotdigitalgroup\Email\Model\Sync\Export\SdkCatalogRecordCollectionBuilderFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroupCollection;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExporterTest extends TestCase
{
    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * @var ProductCollectionFactory|MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var ConnectorProductFactory|MockObject
     */
    private $productFactoryMock;

    /**
     * @var ProductCollection|MockObject
     */
    private $productCollectionMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var CustomerGroupCollection|MockObject
     */
    private $customerGroupCollectionMock;

    /**
     * @var SdkCatalogRecordCollectionBuilderFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sdkCatalogRecordCollectionBuilderFactory;

    /**
     * @var SdkCatalogRecordCollectionBuilderFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sdkCatalogRecordCollectionBuilder;

    /**
     * @var RecordsCollectionAlias|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sdkRecordsCollection;

    protected function setUp() :void
    {
        $this->productCollectionMock = $this->createMock(ProductCollection::class);

        $this->productFactoryMock = $this->createMock(ConnectorProductFactory::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->customerGroupCollectionMock = $this->createMock(CustomerGroupCollection::class);
        $this->collectionFactoryMock = $this->createMock(ProductCollectionFactory::class);
        $this->sdkCatalogRecordCollectionBuilderFactory = $this->createMock(
            SdkCatalogRecordCollectionBuilderFactory::class
        );
        $this->sdkCatalogRecordCollectionBuilder = $this->createMock(SdkCatalogRecordCollectionBuilder::class);
        $this->sdkRecordsCollection = $this->createMock(RecordsCollectionAlias::class);

        $this->exporter = new Exporter(
            $this->loggerMock,
            $this->scopeConfigMock,
            $this->storeManagerMock,
            $this->collectionFactoryMock,
            $this->customerGroupCollectionMock,
            $this->sdkCatalogRecordCollectionBuilderFactory
        );
    }

    /**
     * @dataProvider getProductIdStoreIdsTypesAndVisibilities
     *
     * @param int $storeId
     * @param int $product1Id
     * @param int $product2Id
     * @param string $types
     * @param string $visibilities
     *
     * @return       void
     * @throws Exception
     */
    public function testThatExportKeysAndProductsMatch(
        int $storeId,
        int $product1Id,
        int $product2Id,
        string $types,
        string $visibilities
    ) {
        $productsToProcess = $this->getMockProductsToProcess();
        $this->scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls(
                $types,
                $visibilities
            );

        $this->scopeConfigMock->method('isSetFlag')
            ->willReturn(true);

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->productCollectionMock);

        $this->productCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $this->productCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $this->productCollectionMock->method('addUrlRewrite')->willReturnSelf();
        $this->productCollectionMock->method('addWebsiteNamesToResult')->willReturnSelf();
        $this->productCollectionMock->method('addCategoryIds')->willReturnSelf();
        $this->productCollectionMock->method('addOptionsToResult')->willReturnSelf();

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->productCollectionMock->method('getSelect')->willReturn($selectMock);
        $selectMock->method('joinLeft')->willReturnSelf();

        $productMock1 = $this->getMockProducts($product1Id);
        $productMock2 = $this->getMockProducts($product2Id);

        $exposedProduct1 = $this->getExposedProduct($product1Id);
        $exposedProduct2 = $this->getExposedProduct($product2Id);

        $connectorProductMock1 = $this->getMockConnectorProducts($productMock1, $exposedProduct1);
        $connectorProductMock2 = $this->getMockConnectorProducts($productMock2, $exposedProduct2);

        $this->productCollectionMock->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$productMock1, $productMock2]));

        $this->productCollectionMock->method('getSize')
            ->willReturn(2);

        $this->customerGroupCollectionMock->expects($this->atLeastOnce())
            ->method('toOptionArray')
            ->willReturn([
                ['value' => 1, 'label' => 'General'],
                ['value' => 2, 'label' => 'Wholesale'],
                ['value' => 3, 'label' => 'Retailer']
            ]);

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->productFactoryMock
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls(
                $connectorProductMock1,
                $connectorProductMock2
            );

        $builder = new SdkCatalogRecordCollectionBuilder(
            $this->productFactoryMock,
            $this->loggerMock,
            $storeId // storeId
        );

        $builderResult = $builder->setBuildableData($this->productCollectionMock)->build()->all();
        $this->sdkCatalogRecordCollectionBuilderFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->sdkCatalogRecordCollectionBuilder);

        $this->sdkCatalogRecordCollectionBuilder->expects($this->once())
            ->method('setBuildableData')
            ->with($this->productCollectionMock)
            ->willReturn($this->sdkCatalogRecordCollectionBuilder);

        $this->sdkCatalogRecordCollectionBuilder->expects($this->once())
            ->method('build')
            ->willReturn($this->sdkRecordsCollection);

        $this->sdkRecordsCollection->expects($this->once())
            ->method('all')
            ->willReturn($builderResult);

        $actual = $this->exporter->exportCatalog($storeId, $productsToProcess);
        $actualExposedProduct1 = $actual[$product1Id]->getJson();

        $this->assertEquals($exposedProduct1, $actualExposedProduct1);

        $actualExposedProduct2 = $actual[$product2Id]->getJson();
        $this->assertEquals($exposedProduct2, $actualExposedProduct2);
    }

    /**
     * @dataProvider getProductIdStoreIdsTypesAndVisibilities
     *
     * @param int $storeId
     * @param int $product1Id
     * @param int $product2Id
     * @param string $types
     * @param string $visibilities
     *
     * @return       void
     * @throws Exception
     */
    public function testThatExportKeysAndProductsMatchAtDefaultLevelSync(
        int $storeId,
        int $product1Id,
        int $product2Id,
        string $types,
        string $visibilities
    ) {
        $productsToProcess = $this->getMockProductsToProcess();
        $this->scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls(
                $types,
                $visibilities
            );

        $this->scopeConfigMock->method('isSetFlag')
            ->willReturn(true);

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->productCollectionMock);

        $this->productCollectionMock->method('addAttributeToSelect')->willReturnSelf();
        $this->productCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $this->productCollectionMock->method('addUrlRewrite')->willReturnSelf();
        $this->productCollectionMock->method('addWebsiteNamesToResult')->willReturnSelf();
        $this->productCollectionMock->method('addCategoryIds')->willReturnSelf();
        $this->productCollectionMock->method('addOptionsToResult')->willReturnSelf();

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $this->productCollectionMock->method('getSelect')->willReturn($selectMock);
        $selectMock->method('joinLeft')->willReturnSelf();

        $productMock1 = $this->getMockProducts($product1Id);
        $productMock2 = $this->getMockProducts($product2Id);

        $exposedProduct1 = $this->getExposedProduct($product1Id);
        $exposedProduct2 = $this->getExposedProduct($product2Id);

        $connectorProductMock1 = $this->getMockConnectorProducts($productMock1, $exposedProduct1);
        $connectorProductMock2 = $this->getMockConnectorProducts($productMock2, $exposedProduct2);

        $this->productCollectionMock->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([$productMock1, $productMock2]));

        $this->productCollectionMock->method('getSize')
            ->willReturn(2);

        $this->customerGroupCollectionMock->expects($this->atLeastOnce())
            ->method('toOptionArray')
            ->willReturn([
                ['value' => 1, 'label' => 'General'],
                ['value' => 2, 'label' => 'Wholesale'],
                ['value' => 3, 'label' => 'Retailer']
            ]);

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->productFactoryMock
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls(
                $connectorProductMock1,
                $connectorProductMock2
            );

        $builder = new SdkCatalogRecordCollectionBuilder(
            $this->productFactoryMock,
            $this->loggerMock,
            null // storeId
        );

        $builderResult = $builder->setBuildableData($this->productCollectionMock)->build()->all();
        $this->sdkCatalogRecordCollectionBuilderFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->sdkCatalogRecordCollectionBuilder);

        $this->sdkCatalogRecordCollectionBuilder->expects($this->once())
            ->method('setBuildableData')
            ->with($this->productCollectionMock)
            ->willReturn($this->sdkCatalogRecordCollectionBuilder);

        $this->sdkCatalogRecordCollectionBuilder->expects($this->once())
            ->method('build')
            ->willReturn($this->sdkRecordsCollection);

        $this->sdkRecordsCollection->expects($this->once())
            ->method('all')
            ->willReturn($builderResult);

        $actual = $this->exporter->exportCatalog($storeId, $productsToProcess);
        $actualExposedProduct1 = $actual[$product1Id]->getJson();

        $this->assertEquals($exposedProduct1, $actualExposedProduct1);

        $actualExposedProduct2 = $actual[$product2Id]->getJson();
        $this->assertEquals($exposedProduct2, $actualExposedProduct2);
    }

    /**
     * Returns the mocked Products
     *
     * @param int $productId
     *
     * @return MockObject
     * @throws Exception
     */
    private function getMockProducts(int $productId)
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($productId);

        $product
            ->method('toArray')
            ->willReturn($this->getExposedProduct($productId));

        return $product;
    }

    /**
     * Returns the connector Mock Products
     *
     * @param MockObject $productMock
     * @param array $exposedMock
     *
     * @return MockObject
     * @throws Exception
     */
    private function getMockConnectorProducts(MockObject $productMock, array $exposedMock)
    {
        $connectorProduct = $this->createMock(\Dotdigitalgroup\Email\Model\Connector\Product::class);
        $connectorProduct->expects($this->once())
            ->method('setProduct')
            ->with($productMock)
            ->willReturnSelf();

        $connectorProduct->name = $exposedMock['name'];
        $connectorProduct->id = $exposedMock['id'];

        $connectorProduct
            ->method('toArray')
            ->willReturn($this->getExposedProduct($connectorProduct->id));

        return $connectorProduct;
    }

    /**
     * @return array
     * Returns ids for products and store
     */
    public static function getProductIdStoreIdsTypesAndVisibilities()
    {
        return [
            [1, 1254, 337, '0', '0'],
            [2, 2234, 554, '0', '0'],
            [4, 332, 2445, 'type1,type2', 'visibility1,visibility2']
        ];
    }

    /**
     * Returns product array
     *
     * @return array
     */
    private function getMockProductsToProcess()
    {
        return [
            0 => '1205',
            1 => '1206',
            2 => '1207',
            3 => '1208',
            4 => '1209',
            5 => '1210',
            6 => '1211',
            7 => '1212',
            8 => '1213',
            9 => '1214'
        ];
    }

    /**
     * @param  int $id
     * @return array
     */
    private function getExposedProduct(int $id)
    {

        return [
            'id' => (int) $id,
            'name' => 'product' . $id,
            'parent_id' => '',
            'sku' => '',
            'status' => '',
            'visibility' => '',
            'price' => 0,
            'price_incl_tax' => 0,
            'specialPrice' => 0,
            'specialPrice_incl_tax' => 0,
            'tierPrices' => [],
            'categories' => [],
            'url' => '',
            'imagePath' => '',
            'shortDescription' => '',
            'stock' => 0,
            'websites' => [],
            'type' => ''
        ];
    }
}
