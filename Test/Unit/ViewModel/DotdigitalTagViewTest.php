<?php declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\ViewModel;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Tracking\EndpointResolver;
use Dotdigitalgroup\Email\ViewModel\DotdigitalTagView;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class DotdigitalTagViewTest extends TestCase
{
    #[DataProvider('trackingUrlProvider')]
    public function testRenderScriptUsesResolvedHostWithoutScheme(string $url, string $host): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getWebsiteId')->willReturn(2);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $endpointResolver = $this->createMock(EndpointResolver::class);
        $endpointResolver->expects($this->once())
            ->method('getUrl')
            ->with(2)
            ->willReturn($url);

        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_PATH_CONNECTOR_TRACKING_PROFILE_ID, ScopeInterface::SCOPE_WEBSITE, 2)
            ->willReturn('tag-id');

        $secureRenderer = $this->createMock(SecureHtmlRenderer::class);
        $secureRenderer->expects($this->once())
            ->method('renderTag')
            ->with('script', [], 'window.ddg.init("' . $host . '", "tag-id");', false)
            ->willReturn('<script>rendered</script>');

        $view = new DotdigitalTagView(
            $this->createMock(Context::class),
            $secureRenderer,
            $endpointResolver,
            $scopeConfig,
            $storeManager,
            $this->createMock(Session::class)
        );

        $this->assertSame('<script>rendered</script>', $view->renderScript());
    }

    public static function trackingUrlProvider(): array
    {
        return [
            'default host' => ['https://r2.ddlnk.net', 'r2.ddlnk.net'],
            'custom host' => ['https://r1-gl.ddlnk.net', 'r1-gl.ddlnk.net'],
            'HTTP URL' => ['http://tracking.example.com', 'tracking.example.com']
        ];
    }
}
