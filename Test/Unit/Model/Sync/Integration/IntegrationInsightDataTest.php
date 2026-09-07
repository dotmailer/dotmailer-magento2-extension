<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Integration;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Connector\Module;
use Dotdigitalgroup\Email\Model\Sync\Integration\DotdigitalConfig;
use Dotdigitalgroup\Email\Model\Sync\Integration\IntegrationInsightData;
use Dotdigitalgroup\Email\Model\Sync\Integration\Metrics\MetricProviderInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class IntegrationInsightDataTest extends TestCase
{
    /**
     * @var Data|MockObject
     */
    private $helperMock;

    /**
     * @var ProductMetadataInterface|MockObject
     */
    private $productMetadataMock;

    /**
     * @var DotdigitalConfig|MockObject
     */
    private $dotdigitalConfigMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var Module|MockObject
     */
    private $moduleMock;

    /**
     * @var Store|MockObject
     */
    private $storeMock;

    private function buildSubject(array $metricProviders = []): IntegrationInsightData
    {
        $this->helperMock          = $this->createMock(Data::class);
        $this->productMetadataMock = $this->createMock(ProductMetadataInterface::class);
        $this->dotdigitalConfigMock = $this->createMock(DotdigitalConfig::class);
        $this->storeManagerMock    = $this->createMock(StoreManagerInterface::class);
        $this->moduleMock          = $this->createMock(Module::class);

        $websiteMock = $this->createMock(WebsiteInterface::class);
        $websiteMock->method('getCode')->willReturn('base');

        $this->storeMock = $this->createMock(Store::class);
        $this->storeMock->method('getWebsiteId')->willReturn(1);
        $this->storeMock->method('getWebsite')->willReturn($websiteMock);
        $this->storeMock->method('getBaseUrl')->willReturn('https://example.com/');
        $this->storeMock->method('isCurrentlySecure')->willReturn(true);

        $this->storeManagerMock->method('getStores')->willReturn([$this->storeMock]);

        $this->helperMock->method('isEnabled')->willReturn(true);
        $this->dotdigitalConfigMock->method('getConfig')->willReturn([]);
        $this->productMetadataMock->method('getName')->willReturn('Magento');
        $this->productMetadataMock->method('getEdition')->willReturn('Community');
        $this->productMetadataMock->method('getVersion')->willReturn('2.4.9');
        $this->moduleMock->method('getModuleVersion')->willReturn('4.0.0');

        return new IntegrationInsightData(
            $this->helperMock,
            $this->productMetadataMock,
            $this->dotdigitalConfigMock,
            $this->storeManagerMock,
            $this->moduleMock,
            $metricProviders
        );
    }

    public function testMetricsKeyIsPresentInPayload(): void
    {
        $subject = $this->buildSubject();
        $result  = $subject->getIntegrationInsightData();

        $this->assertArrayHasKey('metrics', reset($result));
    }

    public function testMetricsIsEmptyWhenNoProvidersRegistered(): void
    {
        $subject = $this->buildSubject();
        $result  = $subject->getIntegrationInsightData();

        $this->assertSame([], reset($result)['metrics']);
    }

    public function testMetricProviderDataIsKeyedByProviderName(): void
    {
        $providerMock = $this->createMock(MetricProviderInterface::class);
        $providerMock->method('getMetricData')->willReturn(['foo' => 'bar']);

        $subject = $this->buildSubject(['my_metric' => $providerMock]);
        $result  = $subject->getIntegrationInsightData();
        $metrics = reset($result)['metrics'];

        $this->assertArrayHasKey('my_metric', $metrics);
        $this->assertSame(['foo' => 'bar'], $metrics['my_metric']);
    }

    public function testMultipleProvidersAreAllIncluded(): void
    {
        $providerA = $this->createMock(MetricProviderInterface::class);
        $providerA->method('getMetricData')->willReturn(['a' => 1]);

        $providerB = $this->createMock(MetricProviderInterface::class);
        $providerB->method('getMetricData')->willReturn(['b' => 2]);

        $subject = $this->buildSubject(['alpha' => $providerA, 'beta' => $providerB]);
        $result  = $subject->getIntegrationInsightData();
        $metrics = reset($result)['metrics'];

        $this->assertSame(['a' => 1], $metrics['alpha']);
        $this->assertSame(['b' => 2], $metrics['beta']);
    }

    public function testProviderReceivesWebsiteId(): void
    {
        $expectedWebsiteId = 1;

        $providerMock = $this->createMock(MetricProviderInterface::class);
        $providerMock->expects($this->once())
            ->method('getMetricData')
            ->with($expectedWebsiteId)
            ->willReturn([]);

        $subject = $this->buildSubject(['my_metric' => $providerMock]);
        $subject->getIntegrationInsightData();
    }
}
