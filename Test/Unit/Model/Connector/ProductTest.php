<?php

declare(strict_types=1);

namespace Dotdigitalgroup\Email\Test\Unit\Model\Connector;

use Dotdigitalgroup\Email\Api\StockFinderInterface;
use Dotdigitalgroup\Email\Api\TierPriceFinderInterface;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder;
use Dotdigitalgroup\Email\Model\Connector\Product;
use Dotdigitalgroup\Email\Model\Product\Attribute;
use Dotdigitalgroup\Email\Model\Product\AttributeFactory;
use Dotdigitalgroup\Email\Model\Product\ImageFinder;
use Dotdigitalgroup\Email\Model\Product\ImageType\Context\CatalogSync;
use Dotdigitalgroup\Email\Model\Product\ParentFinder;
use Dotdigitalgroup\Email\Model\Product\PriceFinder;
use Dotdigitalgroup\Email\Model\Product\PriceFinderFactory;
use Dotdigitalgroup\Email\Model\Product\IndexPriceFinder;
use Dotdigitalgroup\Email\Model\Product\TierPriceFinder;
use Dotdigitalgroup\Email\Model\Validator\Schema\Exception\SchemaValidationException;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidator;
use Dotdigitalgroup\Email\Model\Validator\Schema\SchemaValidatorFactory;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product as MageProduct;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Attribute\Source\StatusFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\VisibilityFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Framework\Phrase;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var VisibilityFactory|MockObject
     */
    private $visibilityFactoryMock;

    /**
     * @var StatusFactory|MockObject
     */
    private $statusFactoryMock;

    /**
     * @var MageProduct|MockObject
     */
    private $mageProductMock;

    /**
     * @var Status|MockObject
     */
    private $statusMock;

    /**
     * @var Phrase|MockObject
     */
    private $phraseMock;

    /**
     * @var UrlFinder|MockObject
     */
    private $urlFinderMock;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var Product|MockObject
     */
    private $product;

    /**
     * @var Store|MockObject
     */
    private $storeMock;

    /**
     * @var Attribute|MockObject
     */
    private $attributeMock;

    /**
     * @var AttributeFactory|MockObject
     */
    private $attributeFactoryMock;

    /**
     * @var ParentFinder|MockObject
     */
    private $parentFinderMock;

    /**
     * @var TierPriceFinder|MockObject
     */
    private $tierPriceFinderMock;

    /**
     * @var StockFinderInterface|MockObject
     */
    private $stockFinderInterfaceMock;

    /**
     * @var ImageFinder|MockObject
     */
    private $imageFinderMock;

    /**
     * @var CatalogSync|MockObject
     */
    private $imageTypeMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime|MockObject
     */
    private $dateTimeMock;

    /**
     * @var SchemaValidatorFactory
     */
    private $schemaValidatorFactory;

    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * @var PriceFinderFactory|MockObject
     */
    private $priceFinderFactoryMock;

    /**
     * @var IndexPriceFinder|MockObject
     */
    private $indexPriceFinderMock;

    protected function setUp() :void
    {
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->statusFactoryMock = $this->createMock(StatusFactory::class);
        $this->visibilityFactoryMock = $this->createMock(VisibilityFactory::class);
        $this->mageProductMock = $this->getMockBuilder(MageProduct::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                'getTypeInstance',
                'getSku',
                'getStatus',
                'getTypeId',
                'getPrice',
                'getSpecialPrice',
                'getPriceInfo',
                'getVisibility',
                'getCategoryCollection',
                'getWebsiteIds',
                'getCreatedAt'
                ]
            )
            ->addMethods(
                [
                    'getTaxClassId',
                    'getShortDescription'
                ]
            )
            ->getMock();

        $this->statusMock = $this->createMock(Status::class);
        $this->phraseMock = $this->createMock(Phrase::class);
        $this->urlFinderMock = $this->createMock(UrlFinder::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->attributeMock = $this->createMock(Attribute::class);
        $this->attributeFactoryMock = $this->createMock(AttributeFactory::class);
        $this->parentFinderMock = $this->createMock(ParentFinder::class);
        $this->tierPriceFinderMock = $this->createMock(TierPriceFinderInterface::class);
        $this->stockFinderInterfaceMock = $this->createMock(StockFinderInterface::class);
        $this->imageFinderMock = $this->createMock(ImageFinder::class);
        $this->imageTypeMock = $this->createMock(CatalogSync::class);
        $this->visibility = new Visibility(
            $this->createMock(\Magento\Eav\Model\ResourceModel\Entity\Attribute::class)
        );
        $this->dateTimeMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $this->priceFinderFactoryMock = $this->createMock(PriceFinderFactory::class);
        $this->indexPriceFinderMock = $this->createMock(IndexPriceFinder::class);
        $this->schemaValidatorFactory = $this->createMock(SchemaValidatorFactory::class);
        $this->schemaValidator = $this->createMock(SchemaValidator::class);
        $this->schemaValidatorFactory
            ->method('create')
            ->with(['pattern' => Product::SCHEMA_RULES])
            ->willReturn($this->schemaValidator);

        $this->product = new Product(
            $this->storeManagerMock,
            $this->statusFactoryMock,
            $this->visibilityFactoryMock,
            $this->priceFinderFactoryMock,
            $this->urlFinderMock,
            $this->attributeFactoryMock,
            $this->parentFinderMock,
            $this->imageFinderMock,
            $this->tierPriceFinderMock,
            $this->indexPriceFinderMock,
            $this->stockFinderInterfaceMock,
            $this->imageTypeMock,
            $this->schemaValidatorFactory,
            $this->dateTimeMock
        );
    }

    public function testSetSimpleProductPrices()
    {
        $price = 20.00;
        $price_incl_tax = 24.00;
        $specialPrice = 15.00;
        $specialPrice_incl_tax = 18.00;

        $this->setUpValidator();
        $this->baselineExpectations($price, $price_incl_tax, $specialPrice, $specialPrice_incl_tax);

        $this->mageProductMock->expects($this->once())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->product->setProduct($this->mageProductMock, 1);

        $this->assertEquals($price, $this->product->price);
        $this->assertEquals($specialPrice, $this->product->specialPrice);
        $this->assertEquals($price_incl_tax, $this->product->price_incl_tax);
        $this->assertEquals($specialPrice_incl_tax, $this->product->specialPrice_incl_tax);
    }

    public function testSetSimpleProductPricesNullStore()
    {
        $price = 20.00;
        $specialPrice = 15.00;

        $this->setUpValidator();
        $this->baselineExpectations($price, $price, $specialPrice, $specialPrice);

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->product->setProduct($this->mageProductMock, null);

        $this->assertEquals($price, $this->product->price);
        $this->assertEquals($specialPrice, $this->product->specialPrice);
    }

    public function testIfParentExistsTypeChangedAndParentIdSet()
    {
        $this->setUpValidator();
        $this->baselineExpectations();

        $parentId = 4;

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->parentFinderMock->expects($this->once())
            ->method('getProductParentIdToCatalogSync')
            ->with($this->mageProductMock)
            ->willReturn($parentId);

        $this->product->setProduct($this->mageProductMock, 1);

        $this->assertEquals($this->product->parent_id, $parentId);
        $this->assertEquals($this->product->type, 'Variant');
    }

    public function testIfParentDoesNotExistTypeNotChanged()
    {
        $this->setUpValidator();
        $this->baselineExpectations();

        $parentId = '';

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->parentFinderMock->expects($this->once())
            ->method('getProductParentIdToCatalogSync')
            ->with($this->mageProductMock)
            ->willReturn($parentId);

        $this->product->setProduct($this->mageProductMock, 1);

        $this->assertTrue(isset($this->product->parent_id));
        $this->assertNotEquals($this->product->type, 'Variant');
    }

    public function testCreatedDateFormat()
    {
        $this->setUpValidator();
        $this->baselineExpectations();

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getCreatedAt')
            ->willReturn('2021-02-01 00:00:00');

        $this->dateTimeMock->expects($this->any())
            ->method('date')
            ->with(\DateTimeInterface::ATOM, '2021-02-01 00:00:00')
            ->willReturn('2021-02-01T00:00:00+00:00');

        $this->product->setProduct($this->mageProductMock, 1);

        $createdDate = $this->product->created_date;
        $date = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $createdDate);

        if ($date->format(\DateTimeInterface::ATOM) !== $createdDate) {
            $this->fail('createdDate is not a valid date');
        }

        $this->assertTrue(true, 'createdDate is correctly formatted');
    }

    public function testExceptionThrownIfSchemaNotValid()
    {
        $this->baselineExpectations();

        $this->schemaValidator
            ->method('isValid')
            ->willReturn(false);

        $this->expectException(SchemaValidationException::class);

        $this->product->setProduct($this->mageProductMock, 1);
    }

    public function testGetIndexPrices()
    {
        $this->setUpValidator();
        $this->baselineExpectations();

        $this->indexPriceFinderMock->expects($this->once())
            ->method('getIndexPrices')
            ->with($this->mageProductMock, 1)
            ->willReturn([
                [
                    'customer_group' => 'General',
                    'price' => 10.00,
                    'price_incl_tax' => 12.00,
                    'final_price' => 10.00,
                    'final_price_incl_tax' => 12.00,
                    'min_price' => 10.00,
                    'min_price_incl_tax' => 12.00,
                    'max_price' => 10.00,
                    'max_price_incl_tax' => 12.00,
                    'tier_price' => 10.00,
                    'tier_price_incl_tax' => 12.00
                ]
            ]);

        $this->product->setProduct($this->mageProductMock, 1);
    }

    private function baselineExpectations(
        $price = 0.00,
        $price_incl_tax = 0.00,
        $specialPrice = 0.00,
        $specialPrice_incl_tax = 0.00
    ) {
        $status = 1;
        $visibility = 1;
        $websiteId = 1;
        $websiteIds = [];
        $imageType = [
            'id' => null,
            'role' => 'small_image'
        ];

        $this->statusFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->statusMock);

        $this->mageProductMock->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        $this->statusMock->expects($this->once())
            ->method('getOptionText')
            ->with($status)
            ->willReturn($this->phraseMock);

        $this->visibilityFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->visibility);

        $this->mageProductMock->expects($this->once())
            ->method('getVisibility')
            ->willReturn($visibility);

        $priceFinderMock = $this->createMock(PriceFinder::class);
        $this->priceFinderFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($priceFinderMock);

        $priceFinderMock->expects($this->once())
            ->method('getPrice')
            ->willReturn($price);

        $priceFinderMock->expects($this->once())
            ->method('getSpecialPrice')
            ->willReturn($specialPrice);

        $priceFinderMock->expects($this->once())
            ->method('getPriceInclTax')
            ->willReturn($price_incl_tax);

        $priceFinderMock->expects($this->once())
            ->method('getSpecialPriceInclTax')
            ->willReturn($specialPrice_incl_tax);

        $categoryCollectionMock = $this->createMock(CategoryCollection::class);
        $categoryModelMock = $this->createMock(Category::class);
        $this->mageProductMock->expects($this->once())
            ->method('getCategoryCollection')
            ->willReturn($categoryCollectionMock);

        $categoryCollectionMock->expects($this->once())
            ->method('addNameToResult')
            ->willReturnSelf();

        $categoryCollectionMock->method('getIterator')
            ->willReturn(new \ArrayIterator([$categoryModelMock]));

        $this->mageProductMock->expects($this->once())
            ->method('getWebsiteIds')
            ->willReturn($websiteIds);

        $this->imageTypeMock->expects($this->once())
            ->method('getImageType')
            ->with($websiteId)
            ->willReturn($imageType);

        $this->imageFinderMock->expects($this->once())
            ->method('getImageUrl')
            ->willReturn('http://example.com/image.jpg');

        $this->stockFinderInterfaceMock->expects($this->atLeastOnce())
            ->method('getStockQty')
            ->willReturn(10);

        $this->mageProductMock->expects($this->once())
            ->method('getShortDescription')
            ->willReturn('This here is a product');

        $this->storeManagerMock->expects($this->atLeastOnce())
            ->method('getStore')
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->attributeFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->attributeMock);
    }

    private function setUpValidator()
    {
        $this->schemaValidator
            ->method('isValid')
            ->willReturn(true);
    }
}
