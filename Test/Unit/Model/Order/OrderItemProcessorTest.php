<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Order;

use Dotdigitalgroup\Email\Model\Order\OrderItemOptionProcessor;
use Dotdigitalgroup\Email\Model\Order\OrderItemProcessor;
use Dotdigitalgroup\Email\Model\Product\Attribute;
use Dotdigitalgroup\Email\Model\Product\AttributeFactory;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Sales\Model\Order\Item as ProductItem;
use PHPUnit\Framework\TestCase;

class OrderItemProcessorTest extends TestCase
{
    /**
     * @var AttributeFactory|AttributeFactory&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attributeFactoryMock;

    /**
     * @var OrderItemOptionProcessor|OrderItemOptionProcessor&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $orderItemOptionProcessor;

    /**
     * @var ProductItem|ProductItem&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productItemMock;

    /**
     * @var Product|Product&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productModelMock;

    /**
     * @var OrderItemProcessor
     */
    private $orderItemProcessor;

    /**
     * @var AbstractCollection&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionMock;

    /**
     * @var Attribute|Attribute&\PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attributeMock;

    protected function setUp(): void
    {
        $this->attributeFactoryMock = $this->createMock(AttributeFactory::class);
        $this->orderItemOptionProcessor = $this->createMock(OrderItemOptionProcessor::class);
        $this->productItemMock = $this->createMock(ProductItem::class);
        $this->productModelMock = $this->createMock(Product::class);
        $this->attributeMock = $this->createMock(Attribute::class);
        $this->collectionMock = $this->getMockBuilder(AbstractCollection::class)
            ->onlyMethods(['addAttributeToSelect','getIterator'])
            ->addMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderItemProcessor = new OrderItemProcessor(
            $this->attributeFactoryMock,
            $this->orderItemOptionProcessor,
            $data = ['websiteId' => 1, 'includeCustomOptions' => true]
        );
    }

    public function testThatConfigurableProductReturnNoProductData()
    {
        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getProductType')
            ->willReturn('configurable');

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn('12345');

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getParentItemId')
            ->willReturn(null);

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productModelMock);

        $this->assertFalse($this->orderItemProcessor->process($this->productItemMock));
    }

    public function testSimpleProduct()
    {
        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getProductId')
            ->willReturn('12345');

        $this->initValuesForSimpleAndBundleProducts();

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getProductType')
            ->willReturn('simple');

        $this->productItemMock->expects($this->never())
            ->method('getId');

        $this->productItemMock->expects($this->never())
            ->method('getParentItemId');

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('Chaz hoodie');

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getSku')
            ->willReturn('chazSku');

        $this->attributeMock->expects($this->once())
            ->method('processConfigAttributes')
            ->willReturn($this->attributeMock);

        $this->attributeMock->expects($this->once())
            ->method('hasValues')
            ->willReturn(true);

        $this->attributeMock->expects($this->atLeastOnce())
            ->method('getProperties')
            ->willReturn(new \stdClass());

        $productData = $this->orderItemProcessor->process($this->productItemMock);
        $this->assertValuesForSimpleAndBundleProduct($productData);
    }

    public function testBundleProduct()
    {
        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getProductId')
            ->willReturn('12345');

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getProductType')
            ->willReturn('bundle');

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn('12345');

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getParentItemId')
            ->willReturn('');

        $this->initValuesForSimpleAndBundleProducts();

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturn('Chaz hoodie');

        $this->productModelMock->expects($this->atLeastOnce())
            ->method('getSku')
            ->willReturn('chazSku');

        $this->attributeMock->expects($this->atLeastOnce())
            ->method('hasValues')
            ->willReturn(true);

        $productData = $this->orderItemProcessor->process($this->productItemMock);

        $this->assertValuesForSimpleAndBundleProduct($productData);
    }

    public function testSimpleProductChildOfConfigurableParent()
    {
        $this->productItemMock->expects($this->exactly(8))
            ->method('getProductType')
            ->willReturnOnConsecutiveCalls(
                'configurable',
                'configurable',
                'simple',
                'simple',
                'simple',
                'simple',
                'simple',
                'simple'
            );

        $this->productItemMock->expects($this->exactly(2))
            ->method('getProductId')
            ->willReturnOnConsecutiveCalls('2', '1');

        $this->productItemMock->expects($this->exactly(7))
            ->method('getId')
            ->willReturnOnConsecutiveCalls('1', '1', '1', '1', '1', '1', '1');

        $this->productItemMock->expects($this->exactly(7))
            ->method('getParentItemId')
            ->willReturnOnConsecutiveCalls(null, '1', '1', '1', '1', '1', '1');

        $this->productItemMock->expects($this->exactly(3))
            ->method('getProduct')
            ->willReturn($this->productModelMock);

        // first run for configurable parent
        $this->orderItemProcessor->process($this->productItemMock);

        $this->initValuesForSimpleAndBundleProducts();

        $this->productItemMock->expects($this->exactly(2))
            ->method('getName')
            ->willReturnOnConsecutiveCalls('Chaz hoodie (child)', 'Chaz hoodie (parent)');

        $this->productItemMock->expects($this->once())
            ->method('getSku')
            ->willReturn('chazSku');

        $this->attributeMock->expects($this->exactly(2))
            ->method('hasValues')
            ->willReturn(true);

        // second run for simple child
        $productData = $this->orderItemProcessor->process($this->productItemMock);
        $this->assertArrayHasKey("child_product_attributes", $productData);
        $this->assertEquals($productData["parent_id"], '1');
        $this->assertEquals($productData["parent_name"], 'Chaz hoodie (parent)');
    }

    public function testSimpleProductChildOfBundleParent()
    {
        $this->initValuesForSimpleAndBundleProducts();

        $this->productItemMock->expects($this->exactly(12))
            ->method('getProductType')
            ->willReturnOnConsecutiveCalls(
                'bundle',
                'bundle',
                'simple',
                'simple',
                'simple',
                'simple',
                'simple',
                'simple',
                'simple',
                'simple',
                'simple',
                'bundle'
            );

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn('1');

        $this->productItemMock->expects($this->exactly(4))
            ->method('getProductId')
            ->willReturnOnConsecutiveCalls('2', '2', '2', '1');

        $this->productItemMock->expects($this->exactly(12))
            ->method('getParentItemId')
            ->willReturnOnConsecutiveCalls(
                null,
                '1',
                '1',
                '1',
                '1',
                '1',
                '1',
                '1',
                '1',
                '1',
                '1',
                '1'
            );

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productModelMock);

        $this->productItemMock->expects($this->exactly(4))
            ->method('getName')
            ->willReturn('Bundle product', '', 'Simple product', 'Bundle product');

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getSku')
            ->willReturn('chazSku');

        $this->attributeMock->expects($this->atLeastOnce())
            ->method('hasValues')
            ->willReturn(true);

        //First Call
        $this->orderItemProcessor->process($this->productItemMock);

        $this->initValuesForSimpleAndBundleProducts();

        //Second call
        $productData = $this->orderItemProcessor->process($this->productItemMock);
        $this->assertArrayHasKey("child_product_attributes", $productData);
        $this->assertArrayHasKey("isChildOfBundled", $productData);
        $this->assertEquals($productData["parent_id"], '1');
        $this->assertEquals($productData["parent_name"], 'Bundle product');
    }

    /**
     * This tests a specific bug we introduced.
     *
     * If product attributes have been selected, but we can't load attributes for the parent,
     * while we can for the child (for some reason) - in this case we should not see product
     * attributes in the exported data.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testProductAttributesNotExportedIfAttributesMappedButNoValues()
    {
        $this->productItemMock->expects($this->exactly(8))
            ->method('getProductType')
            ->willReturnOnConsecutiveCalls(
                'configurable',
                'configurable',
                'simple',
                'simple',
                'simple',
                'simple',
                'simple',
                'simple'
            );

        $this->productItemMock->expects($this->exactly(2))
            ->method('getProductId')
            ->willReturnOnConsecutiveCalls('2', '1');

        $this->productItemMock->expects($this->exactly(7))
            ->method('getId')
            ->willReturnOnConsecutiveCalls('1', '1', '1', '1', '1', '1', '1');

        $this->productItemMock->expects($this->exactly(7))
            ->method('getParentItemId')
            ->willReturnOnConsecutiveCalls(null, '1', '1', '1', '1', '1', '1');

        $this->productItemMock->expects($this->exactly(3))
            ->method('getProduct')
            ->willReturn($this->productModelMock);

        // first run for configurable parent
        $this->orderItemProcessor->process($this->productItemMock);

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productModelMock);

        $this->productModelMock->expects($this->atLeastOnce())
            ->method('getCategoryCollection')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->atLeastOnce())
            ->method('addAttributeToSelect');

        $this->collectionMock->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator(
                [$this->collectionMock, $this->collectionMock]
            ));

        $this->attributeFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->attributeMock);

        $this->attributeMock->expects($this->atLeastOnce())
            ->method('getConfigAttributesForSync')
            ->willReturn('size,color');

        $this->attributeMock->expects($this->exactly(2))
            ->method('processConfigAttributes')
            ->willReturn(null, $this->attributeMock);

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getQtyOrdered')
            ->willReturn(1);

        $this->productItemMock->expects($this->exactly(2))
            ->method('getName')
            ->willReturnOnConsecutiveCalls('Chaz hoodie (child)', 'Chaz hoodie (parent)');

        $this->productItemMock->expects($this->once())
            ->method('getSku')
            ->willReturn('chazSku');

        $this->attributeMock->expects($this->once())
            ->method('hasValues')
            ->willReturn(false);

        // second run for simple child
        $productData = $this->orderItemProcessor->process($this->productItemMock);
        $this->assertArrayNotHasKey("product_attributes", $productData);
        $this->assertArrayNotHasKey("child_product_attributes", $productData);
    }

    private function initValuesForSimpleAndBundleProducts()
    {
        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getProduct')
            ->willReturn($this->productModelMock);

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getPrice')
            ->willReturn('52');

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getPriceInclTax')
            ->willReturn('60');

        $this->productModelMock->expects($this->atLeastOnce())
            ->method('getCategoryCollection')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->atLeastOnce())
            ->method('addAttributeToSelect');

        $this->collectionMock->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator(
                [$this->collectionMock, $this->collectionMock]
            ));

        $this->collectionMock->expects($this->atLeastOnce())
            ->method('getName')
            ->willReturnOnConsecutiveCalls('chaz', 'wingman', 'sprat', 'rbv');

        $this->attributeFactoryMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($this->attributeMock);

        $this->attributeMock->expects($this->atLeastOnce())
            ->method('getConfigAttributesForSync')
            ->willReturn('size,color');

        $this->attributeMock->expects($this->atLeastOnce())
            ->method('getAttributeSetName')
            ->willReturn('Chaz');

        $this->productModelMock->expects($this->atLeastOnce())
            ->method('getAttributeSetId')
            ->willReturn(1);

        $this->attributeMock->expects($this->atLeastOnce())
            ->method('processConfigAttributes')
            ->willReturn($this->attributeMock);

        $this->productItemMock->expects($this->atLeastOnce())
            ->method('getQtyOrdered')
            ->willReturn(1);
    }

    private function assertValuesForSimpleAndBundleProduct($productData)
    {
        $this->assertArrayHasKey('product_id', $productData);
        $this->assertArrayHasKey('parent_id', $productData);
        $this->assertArrayHasKey('name', $productData);
        $this->assertArrayHasKey('parent_name', $productData);
        $this->assertArrayHasKey('sku', $productData);
        $this->assertArrayHasKey('qty', $productData);
        $this->assertArrayHasKey('price', $productData);
        $this->assertArrayHasKey('price_inc_tax', $productData);
        $this->assertArrayHasKey('attribute-set', $productData);
        $this->assertArrayHasKey('categories', $productData);
        $this->assertArrayHasKey('product_attributes', $productData);

        $this->assertIsArray($productData);

        $this->assertEquals($productData['parent_id'], '');
        $this->assertEquals($productData['parent_name'], '');
    }
}
