<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Catalog\UrlFinder;

use Dotdigitalgroup\Email\Model\Catalog\UrlFinder as UrlFinder;
use Magento\Bundle\Model\ResourceModel\Selection;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Block\Product\ImageFactory;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\TestCase;

class UrlFinderTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepositoryMock;

    /**
     * @var Configurable|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configurableTypeMock;

    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productMock;

    /**
     * @var Selection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $bundleSelectionMock;

    /**
     * @var Grouped|\PHPUnit_Framework_MockObject_MockObject
     */
    private $groupedTypeMock;

    /**
     * @var StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var WebsiteInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $websiteMock;

    /**
     * @var UrlFinder
     */
    private $urlFinder;

    /**
     * @var ImageFactory
     */
    private $imageFactoryMock;

    protected function setUp()
    {
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->configurableTypeMock = $this->createMock(Configurable::class);
        $this->productMock = $this->createMock(Product::class);
        $this->bundleSelectionMock = $this->createMock(Selection::class);
        $this->groupedTypeMock = $this->createMock(Grouped::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->websiteMock = $this->createMock(Website::class);
        $this->imageFactoryMock = $this->createMock(ImageFactory::class);

        $this->urlFinder = new UrlFinder(
            $this->configurableTypeMock,
            $this->productRepositoryMock,
            $this->bundleSelectionMock,
            $this->groupedTypeMock,
            $this->storeManagerMock,
            $this->imageFactoryMock
        );
    }

    public function testFetchForSimpleVisibleProduct()
    {
        // corresponds to Magento's constant values for visibility levels
        $visibleInCatalogAndSearchInt = 4;

        $this->productMock = $this->getInScopeProduct($this->productMock);

        $this->productMock->expects($this->once())
            ->method('getVisibility')
            ->willReturn($visibleInCatalogAndSearchInt);

        $this->productMock->expects($this->once())
            ->method('getProductUrl');

        $this->productRepositoryMock->expects($this->never())
            ->method('getById');

        $this->urlFinder->fetchFor($this->productMock);
    }

    public function testFetchForSimpleNotVisibleProductWithConfigurableTypeParent()
    {
        $this->productMock = $this->getInScopeProduct($this->productMock);

        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn([10]);

        $this->groupedTypeMock->expects($this->never())
            ->method('getParentIdsByChild');

        $this->bundleSelectionMock->expects($this->never())
            ->method('getParentIdsByChild');

        $this->buildAssertions();
    }

    public function testFetchForSimpleNotVisibleProductWithGroupedTypeParent()
    {
        $this->productMock = $this->getInScopeProduct($this->productMock);

        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn(null);

        $this->groupedTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn([10]);

        $this->bundleSelectionMock->expects($this->never())
            ->method('getParentIdsByChild');

        $this->buildAssertions();
    }

    public function testFetchForSimpleNotVisibleProductWithBundleTypeParent()
    {
        $this->productMock = $this->getInScopeProduct($this->productMock);

        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn(null);

        $this->groupedTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn(null);

        $this->bundleSelectionMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn([10]);

        $this->buildAssertions();
    }

    public function testFetchForSimpleNotVisibleProductWithNoParent()
    {
        $notVisibleInt = 1;

        $this->productMock = $this->getInScopeProduct($this->productMock);

        $this->configurableTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn(null);

        $this->groupedTypeMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn(null);

        $this->bundleSelectionMock->expects($this->once())
            ->method('getParentIdsByChild')
            ->with($this->productMock->getId())
            ->willReturn(null);

        $this->productMock->expects($this->once())
            ->method('getVisibility')
            ->willReturn($notVisibleInt);

        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->productRepositoryMock->expects($this->never())
            ->method('getById');

        $this->productMock->expects($this->once())
            ->method('getProductUrl');

        $this->urlFinder->fetchFor($this->productMock);
    }

    /**
     * Builds all the mutual assertions for all cases
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function buildAssertions()
    {
        // corresponds to Magento's constant values for visibility levels
        $notVisibleInt = 1;

        $this->productMock->expects($this->once())
            ->method('getVisibility')
            ->willReturn($notVisibleInt);

        $this->productMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        // New Product mock for parent
        $parentProduct = $this->createMock(Product::class);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with(10)
            ->willReturn($parentProduct);

        $parentProduct->expects($this->once())
            ->method('getProductUrl');

        $this->urlFinder->fetchFor($this->productMock);
    }

    private function getInScopeProduct($product)
    {
        $productStoreId = 1;
        $storeIdsOfWebsite = [
            0 => 1,
            1 => 2
        ];

        $this->productMock->expects($this->atLeastOnce())
            ->method('getStoreId')
            ->willReturn($productStoreId);

        $this->productMock->expects($this->once())
            ->method('getStoreIds')
            ->willReturn($storeIdsOfWebsite);

        return $product;
    }

    public function testFetchForProductNotInScope()
    {
        $productInWebsites = [0 => 2];
        $productStoreId = 1;
        $storeIdsOfWebsite = [
            0 => 2,
            1 => 3
        ];

        $this->productMock->expects($this->atLeastOnce())
            ->method('getStoreId')
            ->willReturn($productStoreId);

        $this->productMock->expects($this->once())
            ->method('getStoreIds')
            ->willReturn($storeIdsOfWebsite);

        $this->productMock->expects($this->once())
            ->method('getWebsiteIds')
            ->willReturn($productInWebsites);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with($productInWebsites[0])
            ->willReturn($this->websiteMock);

        // Testing the code that hydrates a new product from the repository
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $groupMock = $this->createMock(\Magento\Store\Model\Group::class);

        $this->websiteMock->expects($this->once())
            ->method('getDefaultGroup')
            ->willReturn($storeMock);

        $groupMock->method('getDefaultStoreId')
            ->willReturn(1);

        $newProduct = $this->createMock(Product::class);
        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->willReturn($newProduct);

        $this->urlFinder->fetchFor($this->productMock);
    }

    public function testGetProductImage()
    {
        $imagePath = 'some-image-path';
        $mockImageBlock = $this->createMock(Image::class);
        $mockImageBlock->expects($this->once())
            ->method('getData')
            ->willReturn([
                'image_url' => $imagePath,
            ]);

        $imageId = 'product_small_image';
        $this->imageFactoryMock->expects($this->once())
            ->method('create')
            ->with($this->productMock, $imageId)
            ->willReturn($mockImageBlock);

        $this->productMock = $this->getInScopeProduct($this->productMock);
        $this->assertEquals(
            $imagePath,
            $this->urlFinder->getProductImageUrl($this->productMock, $imageId)
        );
    }
}
