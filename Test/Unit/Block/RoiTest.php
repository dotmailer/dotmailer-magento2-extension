<?php declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Block;

use Dotdigitalgroup\Email\Block\Roi;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Tracking\EndpointResolver;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class RoiTest extends TestCase
{
    #[DataProvider('trackingScriptProvider')]
    public function testTrackingScriptUsesResolvedUrl(string $url, string $version, string $expectedUrl): void
    {
        $website = $this->createMock(Website::class);
        $website->method('getId')->willReturn(2);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getWebsite')->willReturn($website);
        $context = $this->createMock(Context::class);
        $context->method('getStoreManager')->willReturn($storeManager);

        $endpointResolver = $this->createMock(EndpointResolver::class);
        $endpointResolver->expects($this->once())
            ->method('getUrl')
            ->with(2)
            ->willReturn($url);

        $helper = $this->createMock(Data::class);
        $helper->expects($this->once())
            ->method('getTrackingScriptVersionNumber')
            ->willReturn($version);

        $block = new Roi(
            $context,
            $helper,
            $this->createMock(Session::class),
            $endpointResolver
        );

        $this->assertSame($expectedUrl, $block->getPageTrackingUrlForSuccessPage());
    }

    public static function trackingScriptProvider(): array
    {
        return [
            'default host' => ['https://r2.ddlnk.net', '0', 'https://r2.ddlnk.net/_dmmpt'],
            'custom host' => ['https://r1-gl.ddlnk.net', '0', 'https://r1-gl.ddlnk.net/_dmmpt'],
            'versioned script' => ['https://r1-gl.ddlnk.net', '1', 'https://r1-gl.ddlnk.net/_dmmpt.js?v=1']
        ];
    }
}
