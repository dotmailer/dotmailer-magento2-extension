<?php /** @noinspection PhpCSFixerValidationInspection */

namespace Dotdigitalgroup\Email\Test\Unit\Model\Connector;

use Dotdigitalgroup\Email\Api\TierPriceFinderInterface;
use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder;
use Dotdigitalgroup\Email\Model\Connector\Product;
use Dotdigitalgroup\Email\Model\Product\Attribute;
use Dotdigitalgroup\Email\Model\Product\AttributeFactory;
use Dotdigitalgroup\Email\Model\Product\ImageFinder;
use Dotdigitalgroup\Email\Model\Product\ImageType\Context\CatalogSync;
use Dotdigitalgroup\Email\Model\Product\ParentFinder;
use Dotdigitalgroup\Email\Model\Product\TierPriceFinder;
use Dotdigitalgroup\Email\Api\StockFinderInterface;
use Magento\Bundle\Model\Product\Type;
use Magento\Bundle\Model\ResourceModel\Option\Collection as OptionCollection;
use Magento\Bundle\Pricing\Price\BundleRegularPrice;
use Magento\Catalog\Model\Product as MageProduct;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Attribute\Source\StatusFactory;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\Product\Media\ConfigFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\VisibilityFactory;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\Amount\Base as AmountBase;
use Magento\Framework\Pricing\PriceInfo\Base;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Store;
use Magento\Tax\Api\TaxCalculationInterface;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var VisibilityFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $visibilityFactoryMock;

    /**
     * @var StatusFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $statusFactoryMock;

    /**
     * @var MageProduct|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mageProductMock;

    /**
     * @var Status|\PHPUnit_Framework_MockObject_MockObject
     */
    private $statusMock;

    /**
     * @var Phrase|\PHPUnit_Framework_MockObject_MockObject
     */
    private $phraseMock;

    /**
     * @var Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionMock;

    /**
     * @var Configurable|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configurableMock;

    /**
     * @var Base|\PHPUnit_Framework_MockObject_MockObject
     */
    private $baseMock;

    /**
     * @var BundleRegularPrice|\PHPUnit_Framework_MockObject_MockObject
     */
    private $bundleRegularPriceMock;

    /**
     * @var AmountBaseMock|\PHPUnit_Framework_MockObject_MockObject
     */
    private $amountBaseMock;

    /**
     * @var Type|\PHPUnit_Framework_MockObject_MockObject
     */
    private $typeMock;

    /**
     * @var OptionCollection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $optionCollectionMock;

    /**
     * @var UrlFinder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlFinderMock;

    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var Product
     */
    private $product;

    /**
     * @var Store
     */
    private $storeMock;

    /**
     * @var Attribute
     */
    private $attributeMock;

    /**
     * @var AttributeFactory
     */
    private $attributeFactoryMock;

    /**
     * @var ParentFinder\PHPUnit\Framework\MockObject\MockObject
     */
    private $parentFinderMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $parentMock;

    /**
     * @var TierPriceFinder\PHPUnit\Framework\MockObject\MockObject
     */
    private $tierPriceFinderMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $stockFinderInterfaceMock;

    /**
     * @var ImageFinder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $imageFinderMock;

    /**
     * @var CatalogSync|\PHPUnit\Framework\MockObject\MockObject
     */
    private $imageTypeMock;

    /**
     * @var TaxCalculationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $taxCalculationInterfaceMock;

    protected function setUp() :void
    {
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->helperMock = $this->createMock(Data::class);
        $this->statusFactoryMock = $this->createMock(StatusFactory::class);
        $this->visibilityFactoryMock = $this->createMock(VisibilityFactory::class);
        $this->mageProductMock = $this->getMockBuilder(MageProduct::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getTypeInstance',
                'getSku',
                'getStatus',
                'getTypeId',
                'getPrice',
                'getSpecialPrice',
                'getPriceInfo',
                'getVisibility',
                'getCategoryCollection',
                'getWebsiteIds'
            ])
            ->addMethods(['getTaxClassId'])
            ->getMock();
        $this->statusMock = $this->createMock(Status::class);
        $this->phraseMock = $this->createMock(Phrase::class);
        $this->collectionMock = $this->createMock(Collection::class);
        $this->configurableMock = $this->createMock(Configurable::class);
        $this->baseMock = $this->createMock(Base::class);
        $this->bundleRegularPriceMock = $this->createMock(BundleRegularPrice::class);
        $this->amountBaseMock = $this->createMock(AmountBase::class);
        $this->typeMock = $this->createMock(Type::class);
        $this->optionCollectionMock = $this->createMock(OptionCollection::class);
        $this->urlFinderMock = $this->createMock(UrlFinder::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->attributeMock = $this->createMock(Attribute::class);
        $this->attributeFactoryMock = $this->createMock(AttributeFactory::class);
        $this->parentFinderMock = $this->createMock(ParentFinder::class);
        $this->parentMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $this->tierPriceFinderMock = $this->createMock(TierPriceFinderInterface::class);
        $this->stockFinderInterfaceMock = $this->createMock(StockFinderInterface::class);
        $this->imageFinderMock = $this->createMock(ImageFinder::class);
        $this->imageTypeMock = $this->createMock(CatalogSync::class);
        $this->visibility = new Visibility(
            $this->createMock(\Magento\Eav\Model\ResourceModel\Entity\Attribute::class)
        );
        $this->taxCalculationInterfaceMock = $this->createMock(TaxCalculationInterface::class);

        $this->product = new Product(
            $this->storeManagerMock,
            $this->helperMock,
            $this->statusFactoryMock,
            $this->visibilityFactoryMock,
            $this->urlFinderMock,
            $this->attributeFactoryMock,
            $this->parentFinderMock,
            $this->imageFinderMock,
            $this->tierPriceFinderMock,
            $this->stockFinderInterfaceMock,
            $this->imageTypeMock,
            $this->taxCalculationInterfaceMock
        );

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

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getCategoryCollection')
            ->willReturn($this->collectionMock);

        $this->collectionMock->expects($this->once())
            ->method('addNameToResult')
            ->willReturn([]);

        $this->mageProductMock->expects($this->once())
            ->method('getWebsiteIds')
            ->willReturn($websiteIds);

        $this->imageTypeMock->expects($this->once())
            ->method('getImageType')
            ->with($websiteId)
            ->willReturn($imageType);

        $this->imageFinderMock->expects($this->once())
            ->method('getImageUrl')
            ->with($this->mageProductMock, $imageType);

        $this->stockFinderInterfaceMock->expects($this->atLeastOnce())
            ->method('getStockQty');

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

    public function testSetProductFunction()
    {
        $price = '20.00';
        $specialPrice = '15.00';

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('simple');

        $this->mageProductMock->expects($this->once())
            ->method('getPrice')
            ->willReturn($price);

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getSpecialPrice')
            ->willReturn($specialPrice);

        $this->product->setProduct($this->mageProductMock, 1);

        $this->assertEquals($price, $this->product->price);
        $this->assertEquals($specialPrice, $this->product->specialPrice);
    }

    public function testConfigurableMinPrice()
    {
        $minPrice = '15.00';
        $minSpecialPrice = '8.00';

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('configurable');

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeInstance')
            ->willReturn($this->configurableMock);

        $arrayPrices = $this->getArrayPrices();

        $this->configurableMock->expects($this->atLeastOnce())
            ->method('getUsedProducts')
            ->with($this->mageProductMock)
            ->willReturn($arrayPrices);

        $this->product->setProduct($this->mageProductMock, 1);

        $this->assertEquals($minPrice, $this->product->price);
        $this->assertEquals($minSpecialPrice, $this->product->specialPrice);
    }

    public function testConfigurableMinSpecialPriceIsZeroIfSpecialPriceIsNull()
    {
        $minPrice = '15.00';
        $minSpecialPrice = '0.0';

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('configurable');

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeInstance')
            ->willReturn($this->configurableMock);

        $arrayPrices = $this->getArrayNullSpecialPrices();

        $this->configurableMock->expects($this->atLeastOnce())
            ->method('getUsedProducts')
            ->with($this->mageProductMock)
            ->willReturn($arrayPrices);

        $this->product->setProduct($this->mageProductMock, 1);

        $this->assertEquals($minPrice, $this->product->price);
        $this->assertEquals($minSpecialPrice, $this->product->specialPrice);
    }

    public function testBundleMinPrice()
    {
        $minPrice = '10.00';
        $minSpecialPrice = '8.00';

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('bundle');

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getPriceInfo')
            ->willReturn($this->baseMock);

        $this->baseMock->expects($this->atLeastonce())
            ->method('getPrice')
            ->withConsecutive(['regular_price'], ['final_price'])
            ->willReturnOnConsecutiveCalls($this->bundleRegularPriceMock, $this->bundleRegularPriceMock);

        $this->bundleRegularPriceMock->expects($this->atLeastOnce())
            ->method('getMinimalPrice')
            ->willReturnOnConsecutiveCalls($this->amountBaseMock, $this->amountBaseMock);

        $this->amountBaseMock->expects($this->atLeastOnce())
            ->method('getValue')
            ->willReturnOnConsecutiveCalls('10.00', '8.00');

        $this->product->setProduct($this->mageProductMock, 1);

        $this->assertEquals($minPrice, $this->product->price);
        $this->assertEquals($minSpecialPrice, $this->product->specialPrice);
    }

    public function testGroupedProductsMinPrice()
    {
        $minPrice = '15.00';
        $minSpecialPrice = '8.00';

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('grouped');

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeInstance')
            ->willReturn($this->configurableMock);

        $arrayPrices = $this->getArrayPrices();

        $this->configurableMock->expects($this->atLeastOnce())
            ->method('getAssociatedProducts')
            ->with($this->mageProductMock)
            ->willReturn($arrayPrices);

        $this->product->setProduct($this->mageProductMock, 1);

        $this->assertEquals($minPrice, $this->product->price);
        $this->assertEquals($minSpecialPrice, $this->product->specialPrice);
    }

    public function testGroupedProductsMinSpecialPriceIsZeroIfSpecialPriceIsNull()
    {
        $minPrice = '15.00';
        $minSpecialPrice = '0.0';

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeId')
            ->willReturn('grouped');

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getTypeInstance')
            ->willReturn($this->configurableMock);

        $arrayPrices = $this->getArrayNullSPecialPrices();

        $this->configurableMock->expects($this->atLeastOnce())
            ->method('getAssociatedProducts')
            ->with($this->mageProductMock)
            ->willReturn($arrayPrices);

        $this->product->setProduct($this->mageProductMock, 1);

        $this->assertEquals($minPrice, $this->product->price);
        $this->assertEquals($minSpecialPrice, $this->product->specialPrice);
    }

    public function testIfParentExistsTypeChangedAndProductIdSet()
    {
        $parentId = 4;

        $this->parentFinderMock->expects($this->once())
            ->method('getProductParentIdToCatalogSync')
            ->with($this->mageProductMock)
            ->willReturn($parentId);

        $this->product->setProduct($this->mageProductMock, 1);

        $this->assertEquals($this->product->parent_id, $parentId);
        $this->assertEquals($this->product->type, 'Variant');
    }

    public function testIfParentDoesNotExistsTypeNotChangesButParentIdStillSet()
    {
        $parentId = '';

        $this->parentFinderMock->expects($this->once())
            ->method('getProductParentIdToCatalogSync')
            ->with($this->mageProductMock)
            ->willReturn($parentId);

        $this->product->setProduct($this->mageProductMock, 1);

        $this->assertTrue(isset($this->product->parent_id));
        $this->assertNotEquals($this->product->type, 'Variant');
    }

    public function testSetPricesIncTax()
    {
        $price = '20.00';
        $price_incl_tax = '24.00';
        $specialPrice = '15.00';
        $specialPrice_incl_tax = '18.00';
        $taxableGoodsClassId = 2;
        $taxRate = 20.0;

        $this->mageProductMock->expects($this->once())
            ->method('getTaxClassId')
            ->willReturn($taxableGoodsClassId);

        $this->mageProductMock->expects($this->once())
            ->method('getPrice')
            ->willReturn($price);

        $this->mageProductMock->expects($this->atLeastOnce())
            ->method('getSpecialPrice')
            ->willReturn($specialPrice);

        $this->taxCalculationInterfaceMock->expects($this->once())
            ->method('getCalculatedRate')
            ->with($taxableGoodsClassId, null, 1)
            ->willReturn($taxRate);

        $this->product->setProduct($this->mageProductMock, 1);

        $this->assertEquals($price_incl_tax, $this->product->price_incl_tax);
        $this->assertEquals($specialPrice_incl_tax, $this->product->specialPrice_incl_tax);
    }

    private function getArrayPrices()
    {
        $firstElement = $this->createMock(MageProduct::class);
        $firstElement->expects($this->at(0))->method('getPrice')->willReturn('20.00');
        $firstElement->expects($this->at(1))->method('getSpecialPrice')->willReturn('15.00');
        $firstElement->expects($this->at(2))->method('getSpecialPrice')->willReturn('15.00');

        $secondElement = $this->createMock(MageProduct::class);
        $secondElement->expects($this->at(0))->method('getPrice')->willReturn('15.00');
        $secondElement->expects($this->at(1))->method('getSpecialPrice')->willReturn('8.00');
        $secondElement->expects($this->at(2))->method('getSpecialPrice')->willReturn('8.00');

        return $arrayPrices = [$firstElement, $secondElement];
    }

    private function getArrayNullSpecialPrices()
    {
        $firstElement = $this->createMock(MageProduct::class);
        $firstElement->expects($this->at(0))->method('getPrice')->willReturn('20.00');
        $firstElement->expects($this->at(1))->method('getSpecialPrice')->willReturn(null);

        $secondElement = $this->createMock(MageProduct::class);
        $secondElement->expects($this->at(0))->method('getPrice')->willReturn('15.00');
        $secondElement->expects($this->at(1))->method('getSpecialPrice')->willReturn(null);

        return $arrayPrices = [$firstElement, $secondElement];
    }
}
