<?php

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Connector\ProductFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\Collection;
use Dotdigitalgroup\Email\Model\ResourceModel\Catalog\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Catalog\Exporter;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

class ExporterTest extends TestCase
{
    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactoryMock;

    /**
     * @var ProductFactory
     */
    private $productFactoryMock;

    /**
     * @var Data
     */
    private $helperMock;

    /**
     * @var Collection
     */
    private $collectionMock;

    protected function setUp()
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->productFactoryMock = $this->createMock(ProductFactory::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->collectionMock = $this->createMock(Collection::class);

        $this->exporter = new Exporter(
            $this->collectionFactoryMock,
            $this->productFactoryMock,
            $this->helperMock
        );
    }

    /**
     * @dataProvider getProductAndStoreIds
     * @param $storeId
     * @param $product1Id
     * @param $product2Id
     */
    public function testThatExportKeysAndProductsMatches($storeId, $product1Id, $product2Id)
    {
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->collectionMock);

        $productMock1 = $this->getMockProducts($storeId, $product1Id);
        $productMock2 = $this->getMockProducts($storeId, $product2Id);

        $exposedProduct1 = ['name' => 'product1'];
        $exposedProduct2 = ['name' => 'product2'];

        $connectorProductMock1 = $this->getMockConnectorProducts($productMock1, $exposedProduct1);
        $connectorProductMock2 = $this->getMockConnectorProducts($productMock2, $exposedProduct2);

        $products = [$productMock1, $productMock2];

        $this->collectionMock->expects($this->once())
            ->method('getProductsToExportByStore')
            ->with($storeId, 200)
            ->willReturn($products);

        $this->productFactoryMock->expects($this->exactly(2))
            ->method('create')
            ->willReturnOnConsecutiveCalls(
                $connectorProductMock1,
                $connectorProductMock2
            );

        $actual = $this->exporter->exportCatalog($storeId, 200);

        $actualExposedProduct1 = $actual[$product1Id];
        $this->assertEquals($exposedProduct1, $actualExposedProduct1);

        $actualExposedProduct2 = $actual[$product2Id];
        $this->assertEquals($exposedProduct2, $actualExposedProduct2);
    }

    /**
     * Returns the mocked Products
     * @param $storeId
     * @param $productId
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockProducts($storeId, $productId)
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getId')
            ->willReturn($productId);

        return $product;
    }

    /**
     * Returns the connector Mock Products
     * @param $productMock
     * @param $exposedMock
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getMockConnectorProducts($productMock, $exposedMock)
    {
        $connectorProduct = $this->createMock(\Dotdigitalgroup\Email\Model\Connector\Product::class);
        $connectorProduct->expects($this->once())
            ->method('setProduct')
            ->with($productMock)
            ->willReturnSelf();
        $connectorProduct->expects($this->once())
            ->method('expose')
            ->willReturn($exposedMock);

        return $connectorProduct;
    }

    /**
     * @return array
     * Returns ids for products and store
     */
    public function getProductAndStoreIds()
    {
        return [
            [1254,337,1],
            [2234,554,2],
            [332,2445,4]
        ];
    }
}
