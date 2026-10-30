<?php declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\ViewModel;

use Dotdigitalgroup\Email\Api\Product\CurrentProductInterface;
use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\Product\VariantDataEncoder;
use Dotdigitalgroup\Email\Model\Tracking\EndpointResolver;
use Dotdigitalgroup\Email\ViewModel\ProductNotificationView;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ProductNotificationViewTest extends TestCase
{
    public function testProductNotificationScriptUsesResolvedTrackingUrl(): void
    {
        $store = $this->createMock(Store::class);
        $store->method('getId')->willReturn(2);
        $store->method('getWebsiteId')->willReturn(1);

        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnMap([
            [Config::PATH_FOR_ACCOUNT_ID, ScopeInterface::SCOPE_STORE, 2, '12345'],
            [Config::XML_PATH_BACK_IN_STOCK_NOTIFICATION_ID, ScopeInterface::SCOPE_STORE, 2, 'notification-id']
        ]);

        $context = $this->createMock(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        $context->method('getStoreManager')->willReturn($storeManager);

        $endpointResolver = $this->createMock(EndpointResolver::class);
        $endpointResolver->expects($this->once())
            ->method('getUrl')
            ->with(1)
            ->willReturn('https://r1-gl.ddlnk.net');

        $view = new ProductNotificationView(
            $context,
            $this->createMock(CurrentProductInterface::class),
            $endpointResolver,
            $this->createMock(Logger::class),
            $this->createMock(ConfigInterface::class),
            $this->createMock(Visibility::class),
            $this->createMock(VariantDataEncoder::class)
        );

        $this->assertSame(
            'https://r1-gl.ddlnk.net/ddmbis.js?id=9ix&sid=notification-id&t=6&bm=true',
            $view->getProductNotificationScript()
        );
    }
}
