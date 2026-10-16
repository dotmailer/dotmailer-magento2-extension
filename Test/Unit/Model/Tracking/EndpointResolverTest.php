<?php declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Tracking;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Model\Tracking\EndpointResolver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EndpointResolverTest extends TestCase
{
    #[DataProvider('defaultHostProvider')]
    public function testDefaultHostUsesApiRegion(
        ?string $trackingHost,
        string $apiEndpoint,
        int $websiteId,
        string $expectedUrl
    ): void {
        $scope = $websiteId > 0 ? ScopeInterface::SCOPE_WEBSITE : ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap([
                [Config::TRACKING_HOST, $scope, $websiteId, $trackingHost],
                [Config::PATH_FOR_API_ENDPOINT, $scope, $websiteId, $apiEndpoint]
            ]);

        $this->assertSame($expectedUrl, (new EndpointResolver($scopeConfig))->getUrl($websiteId));
    }

    #[DataProvider('customHostProvider')]
    public function testCustomHostDoesNotReadApiEndpoint(string $trackingHost, string $expectedUrl): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(Config::TRACKING_HOST, ScopeInterface::SCOPE_WEBSITE, 3)
            ->willReturn($trackingHost);

        $this->assertSame($expectedUrl, (new EndpointResolver($scopeConfig))->getUrl(3));
    }

    public static function defaultHostProvider(): array
    {
        return [
            'region one' => ['ddlnk.net', 'https://r1-api.dotdigital.com', 1, 'https://r1.ddlnk.net'],
            'region two' => ['ddlnk.net', 'https://r2-api.dotdigital.com', 2, 'https://r2.ddlnk.net'],
            'region three' => ['ddlnk.net', 'https://r3-api.dotdigital.com', 3, 'https://r3.ddlnk.net'],
            'empty host' => ['', 'https://r1-api.dotdigital.com', 1, 'https://r1.ddlnk.net'],
            'unset host' => [null, 'https://r1-api.dotdigital.com', 1, 'https://r1.ddlnk.net'],
            'whitespace' => [' ddlnk.net ', ' https://r1-api.dotdigital.com ', 1, 'https://r1.ddlnk.net'],
            'default scope' => ['ddlnk.net', 'https://r2-api.dotdigital.com', 0, 'https://r2.ddlnk.net'],
            'HTTP endpoint' => ['ddlnk.net', 'http://r1-api.dotdigital.com', 1, 'http://r1.ddlnk.net']
        ];
    }

    public static function customHostProvider(): array
    {
        return [
            'fully qualified host' => ['r1-gl.ddlnk.net', 'https://r1-gl.ddlnk.net'],
            'custom host' => ['tracking.example.com', 'https://tracking.example.com'],
            'HTTPS URL' => ['https://tracking.example.com', 'https://tracking.example.com'],
            'HTTP URL' => ['http://tracking.example.com', 'http://tracking.example.com'],
            'whitespace' => [' r1-gl.ddlnk.net ', 'https://r1-gl.ddlnk.net']
        ];
    }
}
