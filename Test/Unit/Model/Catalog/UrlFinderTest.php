<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Catalog\UrlFinder;

use Dotdigitalgroup\Email\Model\Catalog\UrlFinder as UrlFinder;
use Dotdigitalgroup\Email\Model\Product\ParentFinder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Catalog\Block\Product\ImageBuilderFactory;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
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
     * @var ImageBuilder
     */
    private $imageBuilderMock;

    /**
     * @var Product\Media\Config
     */
    private $mediaConfigMock;

    /**
     * @var
     */
    private $scopeConfigInterfaceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $parentFinderMock;

    protected function setUp()
    {
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->productMock = $this->createMock(Product::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->websiteMock = $this->createMock(Website::class);
        $this->imageBuilderMock = $this->createMock(ImageBuilder::class);
        $this->mediaConfigMock = $this->createMock(Product\Media\Config::class);
        $this->parentFinderMock = $this->createMock(ParentFinder::class);

        $mediaConfigFactory = $this->getMockBuilder(Product\Media\ConfigFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $mediaConfigFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->mediaConfigMock);

        $imageBuilderFactory = $this->getMockBuilder(ImageBuilderFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $imageBuilderFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->imageBuilderMock);

        $this->scopeConfigInterfaceMock = $this->createMock(ScopeConfigInterface::class);

        $this->urlFinder = new UrlFinder(
            $this->productRepositoryMock,
            $this->storeManagerMock,
            $imageBuilderFactory,
            $mediaConfigFactory,
            $this->scopeConfigInterfaceMock,
            $this->parentFinderMock
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
        $imageId = 'product_small_image';

        $this->productMock = $this->getInScopeProduct($this->productMock);

        $this->imageBuilderMock->expects($this->once())
            ->method('setProduct')
            ->with($this->productMock)
            ->willReturn(new class($imageId, $imagePath)
            {
                private $imageId;
                private $imagePath;

                public function __construct($imageId, $imagePath)
                {
                    $this->imageId = $imageId;
                    $this->imagePath = $imagePath;
                }

                public function setImageId($imageId)
                {
                    if ($imageId !== $this->imageId) {
                        throw new \Exception('Image ID did not match');
                    }
                    return $this;
                }

                public function create()
                {
                    return $this;
                }

                public function getData()
                {
                    return [
                        'image_url' => $this->imagePath,
                    ];
                }
            });

        $this->assertEquals(
            $imagePath,
            $this->urlFinder->getProductImageUrl($this->productMock, $imageId)
        );
    }

    public function testGetPathRemovesPubSubStringIfEnabledInConfig()
    {
        $path = 'https://magento2.dev/pub/media/chaz-kangaroo.jpg';

        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->with(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_STRIP_PUB)
            ->willReturn(1);

        $returnedUrl = $this->urlFinder->getPath($path);

        $this->assertFalse(strpos($returnedUrl, '/pub'));
    }

    public function testGetPathNotRemovesPubSubStringIfNotEnabledInConfig()
    {
        $path = 'https://magento2.dev/pub/media/chaz-kangaroo.jpg';

        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->with(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_STRIP_PUB)
            ->willReturn(0);

        $returnedUrl = $this->urlFinder->getPath($path);

        $this->assertGreaterThan(0, strpos($returnedUrl, '/pub'));
    }

    public function testThatRemovePubDoesntBreakUrlsWithNotPubDirectory()
    {
        $path = 'https://magento2.dev/media/chaz-kangaroo.jpg';

        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->with(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_STRIP_PUB)
            ->willReturn(1);

        $returnedUrl = $this->urlFinder->getPath($path);

        $this->assertEquals($returnedUrl, $path);
    }

    public function testThatremovePubRemovesOnlyPubDirectoryAndnotAllSubstrings()
    {
        $path = 'https://simons-pub.com/pub/pub-location/pub-beautifulImage.jpg';

        $expected = 'https://simons-pub.com/pub-location/pub-beautifulImage.jpg';

        $this->scopeConfigInterfaceMock->expects($this->once())
            ->method('getValue')
            ->with(\Dotdigitalgroup\Email\Helper\Config::XML_PATH_CONNECTOR_STRIP_PUB)
            ->willReturn(1);

        $returnedUrl = $this->urlFinder->getPath($path);

        $this->assertEquals($expected, $returnedUrl);
    }
}
