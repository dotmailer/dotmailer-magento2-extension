<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Connector\ContactData;

use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Dotdigitalgroup\Email\Model\Connector\ContactData\ProductLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductLoaderTest extends TestCase
{
    /**
     * @var ProductInterfaceFactory|MockObject
     */
    private $productFactoryMock;

    /**
     * @var ProductResource|MockObject
     */
    private $productResourceMock;

    /**
     * @var ProductLoader
     */
    private $productLoader;

    protected function setUp(): void
    {
        $this->productFactoryMock = $this->getMockBuilder(ProductInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->productResourceMock = $this->getMockBuilder(ProductResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productLoader = new ProductLoader(
            $this->productFactoryMock,
            $this->productResourceMock
        );
    }

    public function testGetProduct(): void
    {
        $productId = 1;
        $storeId = 1;

        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($productMock);

        $productMock->expects($this->once())
            ->method('setStoreId')
            ->with($storeId);

        $this->productResourceMock->expects($this->once())
            ->method('load')
            ->with($productMock, $productId);

        $result = $this->productLoader->getProduct($productId, $storeId);
        $this->assertSame($productMock, $result);

        // Test caching of loaded product
        $resultCached = $this->productLoader->getProduct($productId, $storeId);
        $this->assertSame($productMock, $resultCached);
    }
}
