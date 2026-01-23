<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Product\PwaUrlFinder;
use Laminas\Uri\Http;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class PwaUrlBuilderTest extends TestCase
{
    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Http|\PHPUnit\Framework\MockObject\MockObject
     */
    private $zendUriMock;

    /**
     * @var Product|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productMock;

    /**
     * @var PwaUrlFinder
     */
    private $pwaUrlBuilder;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->zendUriMock = $this->createMock(Http::class);
        $this->productMock = $this->getMockBuilder(Product::class)
            ->addMethods(['getUrlKey'])
            ->onlyMethods(['getProductUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->pwaUrlBuilder = new PwaUrlFinder(
            $this->scopeConfigMock,
            $this->zendUriMock
        );
    }

    public function testBuildPwaUrlWithoutRewrites()
    {
        $pwaUrl = 'https://pwa.example.com/';
        $urlKey = 'my-awesome-product';
        $expectedUrl = 'https://pwa.example.com/my-awesome-product.html';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_PWA_URL_REWRITES)
            ->willReturn(false);

        $this->productMock->expects($this->once())
            ->method('getUrlKey')
            ->willReturn($urlKey);

        $result = $this->pwaUrlBuilder->buildPwaProductUrl($pwaUrl, $this->productMock);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testBuildPwaUrlWithRewrites()
    {
        $pwaUrl = 'https://pwa.example.com/';
        $productUrl = 'https://magento.example.com/catalog/my-awesome-product.html';
        $expectedUrl = 'https://pwa.example.com/catalog/my-awesome-product.html';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_PWA_URL_REWRITES)
            ->willReturn(true);

        $this->productMock->expects($this->once())
            ->method('getProductUrl')
            ->willReturn($productUrl);

        $this->zendUriMock->expects($this->once())
            ->method('parse')
            ->with($productUrl)
            ->willReturnSelf();

        $this->zendUriMock->expects($this->once())
            ->method('getPath')
            ->willReturn('/catalog/my-awesome-product.html');

        $result = $this->pwaUrlBuilder->buildPwaProductUrl($pwaUrl, $this->productMock);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testBuildPwaUrlHandlesTrailingSlash()
    {
        $pwaUrlWithSlash = 'https://pwa.example.com/';
        $pwaUrlWithoutSlash = 'https://pwa.example.com';
        $urlKey = 'my-product';
        $expectedUrl = 'https://pwa.example.com/my-product.html';

        $this->scopeConfigMock->expects($this->exactly(2))
            ->method('getValue')
            ->with(Config::XML_PATH_PWA_URL_REWRITES)
            ->willReturn(false);

        $this->productMock->expects($this->exactly(2))
            ->method('getUrlKey')
            ->willReturn($urlKey);

        // Test with trailing slash
        $result1 = $this->pwaUrlBuilder->buildPwaProductUrl($pwaUrlWithSlash, $this->productMock);
        $this->assertEquals($expectedUrl, $result1);

        // Test without trailing slash
        $result2 = $this->pwaUrlBuilder->buildPwaProductUrl($pwaUrlWithoutSlash, $this->productMock);
        $this->assertEquals($expectedUrl, $result2);
    }
}
