<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Connector\ContactData;

use ArrayIterator;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as CatalogCollectionFactory;
use Dotdigitalgroup\Email\Model\Connector\ContactData\ProductLoader;
use Dotdigitalgroup\Email\Model\Sync\Export\BrandAttributeFinder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductLoaderTest extends TestCase
{
    /**
     * @var BrandAttributeFinder|MockObject
     */
    private $brandAttributeFinderMock;

    /**
     * @var CatalogCollectionFactory|MockObject
     */
    private $catalogCollectionFactoryMock;

    /**
     * @var ProductLoader
     */
    private $productLoader;

    protected function setUp(): void
    {
        $this->brandAttributeFinderMock = $this->createMock(BrandAttributeFinder::class);
        $this->catalogCollectionFactoryMock = $this->getMockBuilder(CatalogCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productLoader = new ProductLoader(
            $this->brandAttributeFinderMock,
            $this->catalogCollectionFactoryMock
        );
    }

    public function testGetCachedProductById(): void
    {
        $productId = 1;
        $storeId = 1;
        $brandAttributeCode = 'brand';

        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();

        $collectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogCollectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $collectionMock->expects($this->once())
            ->method('addStoreFilter')
            ->with($storeId)
            ->willReturn($collectionMock);

        $collectionMock->expects($this->once())
            ->method('addIdFilter')
            ->with([$productId])
            ->willReturn($collectionMock);

        $collectionMock->expects($this->once())
            ->method('addAttributeToSelect')
            ->with($brandAttributeCode)
            ->willReturn($collectionMock);

        $collectionMock->expects($this->once())
            ->method('load')
            ->willReturn($collectionMock);

        $collectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([$productMock]));

        $collectionMock->expects($this->once())
            ->method('getItemById')
            ->with($productId)
            ->willReturn($productMock);

        $this->brandAttributeFinderMock->expects($this->once())
            ->method('getBrandAttributeCodeByStoreId')
            ->willReturn($brandAttributeCode);

        $result = $this->productLoader->getCachedProductById($productId, $storeId);
        $this->assertSame($productMock, $result);

        // Test caching of loaded product
        $resultCached = $this->productLoader->getCachedProductById($productId, $storeId);
        $this->assertSame($productMock, $resultCached);
    }
}
