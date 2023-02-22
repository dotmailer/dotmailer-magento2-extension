<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\Product;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Product\Attribute;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Frontend\AbstractFrontend;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use PHPUnit\Framework\TestCase;

class AttributeTest extends TestCase
{
    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var AttributeCollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeCollectionMock;

    /**
     * @var AttributeSetRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $attributeSetMock;

    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productResourceMock;

    /**
     * @var ProductAttributeRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productAttributeRepositoryMock;

    /**
     * @var SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var DateTimeFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeFactoryMock;

    /**
     * @var Attribute
     */
    private $attribute;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->attributeCollectionMock = $this->createMock(AttributeCollectionFactory::class);
        $this->attributeSetMock = $this->createMock(AttributeSetRepositoryInterface::class);
        $this->productResourceMock = $this->createMock(Product::class);
        $this->productAttributeRepositoryMock = $this->createMock(ProductAttributeRepositoryInterface::class);
        $this->searchCriteriaBuilderMock = $this->createMock(SearchCriteriaBuilder::class);
        $this->dateTimeFactoryMock = $this->createMock(DateTimeFactory::class);

        $this->attribute = new Attribute(
            $this->helperMock,
            $this->attributeCollectionMock,
            $this->attributeSetMock,
            $this->productResourceMock,
            $this->productAttributeRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->dateTimeFactoryMock
        );
    }

    public function  testCanProcessProductAttributesYieldingNestedArrays()
    {
        $configAttributes = ['color', 'size', 'width', 'height', 'chaz', 'category_ids', 'random_int'];
        $attributesFromAttributeSet = ['size', 'chaz', 'category_ids', 'media_gallery', 'random_int'];
        $productModelMock = $this->createMock(\Magento\Catalog\Model\Product::class);

        $abstractAttributeMock = $this->createMock(AbstractAttribute::class);
        $this->productResourceMock->expects($this->atLeastOnce())->method('getAttribute')->willReturn($abstractAttributeMock);
        $frontEndMock = $this->createMock(AbstractFrontend::class);
        $abstractAttributeMock->expects($this->atLeastOnce())->method('getFrontend')->willReturn($frontEndMock);
        $frontEndMock->expects($this->atLeastOnce())->method('getInputType');

        $productModelMock->expects($this->exactly(4))
            ->method('getData')
            ->willReturnOnConsecutiveCalls(
                'medium',
                [
                    'values' => [
                        "chaz" => [
                            "foo",
                            "bar"
                        ],
                        "baz" => [
                            "big",
                            "bob"
                        ]
                    ]
                ],
                [
                    'values' => ['Beer', 'Wine', 'Soft drinks']
                ],
                7
            );

        $this->attribute->processConfigAttributes($configAttributes, $attributesFromAttributeSet, $productModelMock);

        $this->assertEquals($this->attribute->getProperties()->size, 'medium');
        $this->assertEquals($this->attribute->getProperties()->chaz, 'foo,bar,big,bob');
        $this->assertEquals($this->attribute->getProperties()->category_ids, 'Beer,Wine,Soft drinks');
        $this->assertEquals($this->attribute->getProperties()->random_int, '7');
    }
}
