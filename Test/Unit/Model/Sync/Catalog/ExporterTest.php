<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Catalog;

use Dotdigitalgroup\Email\Model\Connector\ProductFactory;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\Collection;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog\Exporter;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExporterTest extends TestCase
{
    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * @var CollectionFactory|MockObject
     */
    private $collectionFactoryMock;

    /**
     * @var ProductFactory|MockObject
     */
    private $productFactoryMock;

    /**
     * @var Collection|MockObject
     */
    private $collectionMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfigMock;

    /**
     * @var StoreManagerInterface
     */
    private $storeManagerMock;

    protected function setUp() :void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->productFactoryMock = $this->createMock(ProductFactory::class);
        $this->collectionMock = $this->createMock(Collection::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);

        $this->exporter = new Exporter(
            $this->collectionFactoryMock,
            $this->productFactoryMock,
            $this->loggerMock,
            $this->scopeConfigMock,
            $this->storeManagerMock
        );
    }

    /**
     * @dataProvider getProductIdStoreIdsTypesAndVisibilities
     * @param int $storeId
     * @param int $product1Id
     * @param int $product2Id
     * @param string $types
     * @param string $visibilities
     * @return void
     */
    public function testThatExportKeysAndProductsMatch(
        int $storeId,
        int $product1Id,
        int $product2Id,
        string $types,
        string $visibilities
    ) {
        $productsToProcess = $this->getMockProductsToProcess();

        $storeMock = $this->createMock(StoreInterface::class);
        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);

        $storeMock->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn(1);

        $this->scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnOnConsecutiveCalls(
                $types,
                $visibilities
            );

        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $productMock1 = $this->getMockProducts($product1Id);
        $productMock2 = $this->getMockProducts($product2Id);

        $exposedProduct1 = $this->getExposedProduct($product1Id);
        $exposedProduct2 = $this->getExposedProduct($product2Id);

        $connectorProductMock1 = $this->getMockConnectorProducts($productMock1, $exposedProduct1);
        $connectorProductMock2 = $this->getMockConnectorProducts($productMock2, $exposedProduct2);

        $products = [$productMock1, $productMock2];

        $this->collectionMock->expects($this->once())
            ->method('filterProductsByStoreTypeAndVisibility')
            ->with($storeId, $productsToProcess)
            ->willReturn($products);

        $this->productFactoryMock
            ->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls(
                $connectorProductMock1,
                $connectorProductMock2
            );

        $actual = $this->exporter->exportCatalog($storeId, $productsToProcess);

        $actualExposedProduct1 = $actual[$product1Id];
        $this->assertEquals($exposedProduct1, $actualExposedProduct1);

        $actualExposedProduct2 = $actual[$product2Id];
        $this->assertEquals($exposedProduct2, $actualExposedProduct2);
    }

    /**
     * Returns the mocked Products
     * @param int $productId
     * @return MockObject
     */
    private function getMockProducts(int $productId)
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getId')
            ->willReturn($productId);

        $product
            ->method('toArray')
            ->willReturn($this->getExposedProduct($productId));

        return $product;
    }

    /**
     * Returns the connector Mock Products
     * @param MockObject $productMock
     * @param array $exposedMock
     * @return MockObject
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
    public function getProductIdStoreIdsTypesAndVisibilities()
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
     * @param int $id
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
