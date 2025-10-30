<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product\Aggregation;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use PHPUnit\Framework\TestCase;
use Dotdigitalgroup\Email\Model\Product\Aggregation\TrackingProductAggregation;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductGeneralProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductStockProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductPriceProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductTaxonomyProviderInterface;
use Dotdigitalgroup\Email\Api\Model\Product\Provider\Attributes\ProductMediaProviderInterface;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder;

class TrackingProductAggregationTest extends TestCase
{
    public function testToArrayReturnsCorrectValues()
    {
        $urlFinderMock = $this->createMock(UrlFinder::class);
        $urlFinderMock->method('fetchFor')->willReturn('http://example.com/product');

        $generalProviderMock = $this->getMockBuilder(ProductGeneralProviderInterface::class)
            ->onlyMethods(['getSku', 'getDescription', 'getId', 'getName', 'getVisibility', 'getUrl', 'getType'])
            ->getMock();

        $generalProviderMock->method('getUrl')->willReturn($urlFinderMock->fetchFor(null));
        $generalProviderMock->method('getSku')->willReturn('SKU123');
        $generalProviderMock->method('getDescription')->willReturn('Product Description');
        $generalProviderMock->method('getId')->willReturn(1);
        $generalProviderMock->method('getName')->willReturn('Product Name');
        $generalProviderMock->method('getType')->willReturn('simple');

        $stockProviderMock = $this->createMock(ProductStockProviderInterface::class);
        $stockProviderMock->method('getStatus')->willReturn('In Stock');
        $stockProviderMock->method('getStockQuantity')->willReturn(100);

        $priceProviderMock = $this->createMock(ProductPriceProviderInterface::class);
        $priceProviderMock->method('getPrice')->willReturn(99.99);
        $priceProviderMock->method('getPriceInclTax')->willReturn(119.99);
        $priceProviderMock->method('getSalePrice')->willReturn(79.99);
        $priceProviderMock->method('getSalePriceInclTax')->willReturn(95.99);
        $priceProviderMock->method('getCurrencyCode')->willReturn('USD');

        $taxonomyProviderMock = $this->createMock(ProductTaxonomyProviderInterface::class);
        $taxonomyProviderMock->method('getBrand')->willReturn("Brand Name");

        $categoryMock = $this->createMock(Category::class);
        $categoryMock->method('getId')->willReturn(1);
        $categoryMock->method('getName')->willReturn('Category Name');

        $categoryCollectionMock = $this->createMock(Collection::class);
        $categoryCollectionMock->method('getItems')->willReturn([$categoryMock]);

        $taxonomyProviderMock->method('getCategories')->willReturn($categoryCollectionMock);

        $mediaProviderMock = $this->createMock(ProductMediaProviderInterface::class);
        $mediaProviderMock->method('getImagePath')->willReturn('/path/to/image.jpg');

        $aggregation = new TrackingProductAggregation(
            $generalProviderMock,
            $stockProviderMock,
            $priceProviderMock,
            $taxonomyProviderMock,
            $mediaProviderMock
        );

        $expectedArray = [
            'productId' => 1,
            'name' => 'Product Name',
            'url' => 'http://example.com/product',
            'stock' => 100,
            'currency' => 'USD',
            'status' => 'In Stock',
            'price' => 99.99,
            'priceInclTax' => 119.99,
            'sku' => 'SKU123',
            'brand' => 'Brand Name',
            'categories' => ['Category Name'],
            'imageUrl' => '/path/to/image.jpg',
            'description' => 'Product Description',
            'extraData' => [
                'type' => 'simple'
            ],
            'salePrice' => 79.99,
            'salePriceInclTax' => 95.99
        ];

        $this->assertEquals($expectedArray, $aggregation->toArray());
    }
}
