<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Sync\Export;

use Dotdigitalgroup\Email\Model\Sync\Export\BrandAttributeFinder;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class BrandAttributeFinderTest extends TestCase
{
    /**
     * @var ProductResource|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productResourceMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    /**
     * @var BrandAttributeFinder
     */
    private $brandAttributeFinder;

    protected function setUp(): void
    {
        $this->productResourceMock = $this->createMock(ProductResource::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);

        $this->brandAttributeFinder = new BrandAttributeFinder(
            $this->productResourceMock,
            $this->scopeConfigMock
        );
    }

    public function testGetBrandAttribute()
    {
        $websiteId = 1;
        $attributeCode = 'brand';
        $attributeMock = $this->createMock(AbstractAttribute::class);

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('connector_configuration/data_fields/brand_attribute', 'websites', $websiteId)
            ->willReturn($attributeCode);

        $this->productResourceMock->expects($this->once())
            ->method('getAttribute')
            ->with($attributeCode)
            ->willReturn($attributeMock);

        $result = $this->brandAttributeFinder->getBrandAttribute($websiteId);
        $this->assertSame($attributeMock, $result);
    }

    public function testGetBrandAttributeThrowsException()
    {
        $websiteId = 1;
        $attributeCode = 'brand';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('connector_configuration/data_fields/brand_attribute', 'websites', $websiteId)
            ->willReturn($attributeCode);

        $this->productResourceMock->expects($this->once())
            ->method('getAttribute')
            ->with($attributeCode)
            ->willThrowException(new LocalizedException(__('Error')));

        $result = $this->brandAttributeFinder->getBrandAttribute($websiteId);
        $this->assertFalse($result);
    }

    public function testGetBrandAttributeCodeByStoreId()
    {
        $storeId = 1;
        $attributeCode = 'brand';

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('connector_configuration/data_fields/brand_attribute', 'stores', $storeId)
            ->willReturn($attributeCode);

        $result = $this->brandAttributeFinder->getBrandAttributeCodeByStoreId($storeId);
        $this->assertSame($attributeCode, $result);
    }
}
