<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Product\ImageFinder;
use Dotdigitalgroup\Email\Model\Product\ParentFinder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;

class ImageFinderTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $productRepositoryMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    /**
     * @var ImageFinder
     */
    private $imageFinder;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $storeMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $productMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $itemMock;

    /**
     * @var ParentFinder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $parentFinderMock;
    
    /**
     * @var Logger|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    protected function setUp() :void
    {
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->productMock = $this->createMock(Product::class);
        $this->itemMock = $this->createMock(Item::class);
        $this->parentFinderMock = $this->createMock(ParentFinder::class);
        $this->loggerMock = $this->createMock(Logger::class);

        $this->storeMock->expects($this->once())
            ->method('getBaseUrl')
            ->with(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true)
            ->willReturn('https://magentostore.com/');

        $this->imageFinder = new ImageFinder(
            $this->productRepositoryMock,
            $this->scopeConfigMock,
            $this->parentFinderMock,
            $this->loggerMock
        );
    }

    public function testGetProductImageUrl()
    {
        $this->itemMock->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productMock);

        $this->productMock->expects($this->atLeastOnce())
            ->method('__call')
            ->withConsecutive(
                [$this->equalTo('getThumbnail')],
                [$this->equalTo('getThumbnail')],
                [$this->equalTo('getThumbnail')],
                [$this->equalTo('getThumbnail')]
            )
            ->willReturnOnConsecutiveCalls(
                '/image.jpg',
                '/image.jpg',
                '/image.jpg',
                '/image.jpg'
            );

        $this->imageFinder->getProductImageUrl(
            $this->itemMock,
            $this->storeMock
        );
    }
}
