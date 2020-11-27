<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder;
use Dotdigitalgroup\Email\Model\Product\ImageFinder;
use Dotdigitalgroup\Email\Model\Product\ParentFinder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\Product\Media\ConfigFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Item;

use PHPUnit\Framework\TestCase;

class ImageFinderTest extends TestCase
{
    /**
     * @var UrlFinder\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlFinderMock;

    /**
     * @var ParentFinder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $parentFinderMock;

    /**
     * @var ProductRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productRepositoryMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Product|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productMock;

    /**
     * @var Item|\PHPUnit\Framework\MockObject\MockObject
     */
    private $itemMock;

    /**
     * @var Config|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mediaConfigMock;

    /**
     * @var ConfigFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mediaConfigFactoryMock;

    /**
     * @var Image|\PHPUnit\Framework\MockObject\MockObject
     */
    private $imageHelperMock;

    /**
     * @var ImageFinder
     */
    private $imageFinder;

    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    protected function setUp() :void
    {
        $this->urlFinderMock = $this->createMock(UrlFinder::class);
        $this->parentFinderMock = $this->createMock(ParentFinder::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->productMock = $this->createMock(Product::class);
        $this->itemMock = $this->createMock(Item::class);
        $this->mediaConfigMock = $this->createMock(Config::class);
        $this->mediaConfigFactoryMock = $this->getMockBuilder(ConfigFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->imageHelperMock = $this->createMock(Image::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->imageFinder = new ImageFinder(
            $this->urlFinderMock,
            $this->parentFinderMock,
            $this->productRepositoryMock,
            $this->scopeConfigMock,
            $this->mediaConfigFactoryMock,
            $this->imageHelperMock,
            $this->loggerMock
        );
    }

    public function testGetCartImageUrlIfConfigurableProductImageIsItself()
    {
        $configurableProductImage = 'itself';
        $storeId = 1;
        $path = '/c/d/chaz.jpg';
        $settings = [
            'id' => null,
            'role' => 'thumbnail'
        ];

        $this->itemMock->expects($this->once())
            ->method('getProductType')
            ->willReturn('configurable');

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn($configurableProductImage);

        $this->itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->productMock->expects($this->once())
            ->method('getIdBySku')
            ->willReturn(1);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->willReturn($this->productMock);

        $this->loadImageByRole($path, $settings);

        $this->imageFinder->getCartImageUrl(
            $this->itemMock,
            $storeId,
            $settings
        );
    }

    public function testGetCartImageUrlIfConfigurableProductImageIsParent()
    {
        $configurableProductImage = 'parent';
        $storeId = 1;
        $path = '/c/d/chaz.jpg';
        $settings = [
            'id' => null,
            'role' => 'thumbnail'
        ];

        $this->itemMock->expects($this->once())
            ->method('getProductType')
            ->willReturn('configurable');

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn($configurableProductImage);

        $this->itemMock->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->willReturn($this->productMock);

        $this->loadImageByRole($path, $settings);

        $this->imageFinder->getCartImageUrl(
            $this->itemMock,
            $storeId,
            $settings
        );
    }

    public function testGetCartImageUrlIfGroupedProductImageIsItself()
    {
        $groupedProductImage = 'itself';
        $storeId = 1;
        $path = '/c/d/chaz.jpg';
        $settings = [
            'id' => null,
            'role' => 'thumbnail'
        ];

        $this->itemMock->expects($this->once())
            ->method('getProductType')
            ->willReturn('grouped');

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn($groupedProductImage);

        $this->itemMock->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->productMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->willReturn($this->productMock);

        $this->loadImageByRole($path, $settings);

        $this->imageFinder->getCartImageUrl(
            $this->itemMock,
            $storeId,
            $settings
        );
    }

    public function testGetCartImageUrlIfGroupedProductImageIsParent()
    {
        $groupedProductImage = 'parent';
        $storeId = 1;
        $path = '/c/d/chaz.jpg';
        $settings = [
            'id' => null,
            'role' => 'thumbnail'
        ];

        $this->itemMock->expects($this->once())
            ->method('getProductType')
            ->willReturn('grouped');

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn($groupedProductImage);

        $this->itemMock->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->parentFinderMock->expects($this->once())
            ->method('getParentProduct')
            ->with($this->productMock)
            ->willReturn($this->productMock);

        $this->productMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->willReturn($this->productMock);

        $this->loadImageByRole($path, $settings);

        $this->imageFinder->getCartImageUrl(
            $this->itemMock,
            $storeId,
            $settings
        );
    }

    public function testGetCartImageUrlIfFoundProductThumbnailIsNoSelection()
    {
        $configurableProductImage = 'parent';
        $storeId = 1;
        $path = 'no_selection';
        $settings = [
            'id' => null,
            'role' => 'thumbnail'
        ];

        $this->itemMock->expects($this->once())
            ->method('getProductType')
            ->willReturn('configurable');

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->willReturn($configurableProductImage);

        $this->itemMock->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->willReturn($this->productMock);

        $this->productMock->expects($this->once())
            ->method('getData')
            ->with($settings['role'])
            ->willReturn($path);

        $noImageFound = $this->imageFinder->getCartImageUrl(
            $this->itemMock,
            $storeId,
            $settings
        );

        $this->assertEquals($noImageFound, "");
    }

    public function testGetImageUrlReturnsNullIfSettingsEmpty()
    {
        $settings = [
            'id' => null,
            'role' => null
        ];

        $imageUrl = $this->imageFinder->getImageUrl(
            $this->productMock,
            $settings
        );

        $this->assertEquals($imageUrl, null);
    }

    public function testGetImageLoadsCachedImageById()
    {
        $settings = [
            'id' => 'tiny_chaz',
            'role' => 'chaz_image'
        ];

        $this->urlFinderMock->expects($this->once())
            ->method('getPath');

        $this->parentFinderMock->expects($this->once())
            ->method('getParentProductForNoImageSelection')
            ->with($this->productMock, $settings['role'])
            ->willReturn($this->productMock);

        $this->imageHelperMock->expects($this->once())
            ->method('init')
            ->with($this->productMock, $settings['id'])
            ->willReturn($this->imageHelperMock);

        $this->imageFinder->getImageUrl(
            $this->productMock,
            $settings
        );
    }

    public function testGetImageUrlLoadsImageRole()
    {
        $path = '/c/d/chaz.jpg';
        $settings = [
            'id' => null,
            'role' => 'chaz_image'
        ];

        $this->loadImageByRole($path, $settings);

        $this->imageFinder->getImageUrl(
            $this->productMock,
            $settings
        );
    }

    private function loadImageByRole($path, $settings)
    {
        $this->mediaConfigFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->mediaConfigMock);

        $this->parentFinderMock->expects($this->once())
            ->method('getParentProductForNoImageSelection')
            ->with($this->productMock, $settings['role'])
            ->willReturn($this->productMock);

        $this->productMock->expects($this->atLeastOnce())
            ->method('getData')
            ->with($settings['role'])
            ->willReturn($path);

        $this->mediaConfigMock->expects($this->once())
            ->method('getMediaUrl')
            ->with($path);
    }
}
