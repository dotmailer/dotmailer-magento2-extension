<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\AbandonedCart\CartInsight;

use DateTimeInterface;
use Dotdigital\Resources\AbstractResource;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data as CartInsightData;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\ClientFactory;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder;
use Dotdigitalgroup\Email\Model\Product\ImageFinder;
use Dotdigitalgroup\Email\Model\Product\ImageType\Context\AbandonedCart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @var Client|MockObject
     */
    private $clientMock;

    /**
     * @var ClientFactory|MockObject
     */
    private $clientFactoryMock;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepositoryMock;

    /**
     * @var Emulation|MockObject
     */
    private $emulationMock;

    /**
     * @var Product|MockObject
     */
    private $productMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerInterfaceMock;

    /**
     * @var DateTime|MockObject
     */
    private $dateTimeMock;

    /**
     * @var CartTotalRepositoryInterface|MockObject
     */
    private $cartTotalRepositoryMock;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    /**
     * @var Item|MockObject
     */
    private $itemMock;

    /**
     * @var CartInsightData
     */
    private $class;

    /**
     * @var int
     */
    private $websiteId = 10;

    /**
     * @var int
     */
    private $storeId = 1;

    /**
     * @var Store|MockObject
     */
    private $storeMock;

    /**
     * @var UrlFinder|MockObject
     */
    private $urlFinderMock;

    /**
     * @var ImageFinder|MockObject
     */
    private $imageFinderMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var AbandonedCart|MockObject
     */
    private $imageTypeMock;

    /**
     * @var PriceCurrencyInterface|MockObject
     */
    private $priceCurrencyInterfaceMock;

    /**
     * @var AbstractResource|MockObject
     */
    private $abstractResourceMock;

    protected function setUp() :void
    {
        $this->clientFactoryMock = $this->createMock(ClientFactory::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->emulationMock = $this->createMock(Emulation::class);
        $this->productMock = $this->createMock(Product::class);
        $this->storeManagerInterfaceMock = $this->createMock(StoreManagerInterface::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->quoteMock = $this->createMock(Quote::class);
        $this->itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'getProductType',
                    'getSku',
                    'getName',
                    'getQty',
                    'getPrice'
                ]
            )
            ->addMethods(
                [
                    'getBasePrice',
                    'getDiscountAmount',
                    'getRowTotal',
                    'getRowTotalInclTax',
                    'getTaxPercent',
                    'getPriceInclTax'
                ]
            )
            ->getMock();
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->urlFinderMock = $this->createMock(UrlFinder::class);
        $this->imageFinderMock = $this->createMock(ImageFinder::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->imageTypeMock = $this->createMock(AbandonedCart::class);
        $this->priceCurrencyInterfaceMock = $this->createMock(PriceCurrencyInterface::class);
        $this->cartTotalRepositoryMock = $this->createMock(CartTotalRepositoryInterface::class);

        $this->clientMock = $this->createMock(Client::class);
        $this->abstractResourceMock = $this->getMockBuilder(AbstractResource::class)
            ->disableOriginalConstructor()
            ->addMethods(['createOrUpdateContactCollectionRecord'])
            ->getMock();
        $this->clientMock->insightData = $this->abstractResourceMock;

        $this->class = new CartInsightData(
            $this->clientFactoryMock,
            $this->storeManagerInterfaceMock,
            $this->productRepositoryMock,
            $this->emulationMock,
            $this->dateTimeMock,
            $this->cartTotalRepositoryMock,
            $this->urlFinderMock,
            $this->imageFinderMock,
            $this->loggerMock,
            $this->imageTypeMock,
            $this->priceCurrencyInterfaceMock
        );
    }

    public function testSendFunction()
    {
        $this->setStoreMock();

        $this->clientFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->clientMock);

        $key = "12345";
        $expectedPayload = $this->getMockPayload();

        $this->quoteMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($key);

        $matcher = $this->exactly(3);
        $this->quoteMock->expects($this->atLeastOnce())
            ->method('__call')
            ->willReturnCallback(function () use ($matcher, $expectedPayload) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [$this->equalTo('getQuoteCurrencyCode')],
                    2 => [$this->equalTo('getSubtotal')],
                    3 => [$this->equalTo('getGrandTotal')],
                    4 => [$this->equalTo('getCustomerEmail')],
                };
            })
            ->willReturnOnConsecutiveCalls(
                $expectedPayload['currency'],
                $expectedPayload['subTotal'],
                $expectedPayload['grandTotal'],
                'chaz@emailsim.io'
            );

        $totalMock = $this->createMock(TotalsInterface::class);
        $this->cartTotalRepositoryMock->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($totalMock);

        $totalMock->expects($this->once())
            ->method('getSubtotalInclTax')
            ->willReturn($expectedPayload['subtotal_incl_tax']);

        $this->storeMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($this->storeId);

        $this->storeMock->expects($this->once())
            ->method('getUrl')
            ->with(
                'connector/email/getbasket',
                ['quote_id' => $key]
            )
            ->willReturn($expectedPayload['cartUrl']);

        // Dates
        $createdAt = "2018-03-31 18:53:28";
        $updatedAt = "2018-03-31 19:53:28";

        $this->quoteMock->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($createdAt);

        $this->quoteMock->expects($this->once())
            ->method('getUpdatedAt')
            ->willReturn($updatedAt);

        $matcher = $this->exactly(2);
        $this->dateTimeMock
            ->method('date')
            ->willReturnCallback(function () use ($matcher, $createdAt, $updatedAt) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [DateTimeInterface::ATOM, $createdAt],
                    2 => [DateTimeInterface::ATOM, $updatedAt],
                };
            })
            ->willReturnOnConsecutiveCalls(
                $expectedPayload['createdDate'],
                $expectedPayload['modifiedDate']
            );

        $addressMock = $this->createMock(Quote\Address::class);
        $this->quoteMock->expects($this->atLeastOnce())
            ->method('getShippingAddress')
            ->willReturn($addressMock);

        $matcher = $this->exactly(2);
        $addressMock->expects($this->atLeastOnce())
            ->method('__call')
            ->willReturnCallback(function () use ($matcher, $expectedPayload) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [$this->equalTo('getTaxAmount')],
                    2 => [$this->equalTo('getShippingAmount')]
                };
            })
            ->willReturnOnConsecutiveCalls(
                $expectedPayload['taxAmount'],
                $expectedPayload['shipping']
            );

        // Line items loop
        $itemsArray = [
            $this->itemMock
        ];

        $this->quoteMock->expects($this->once())
            ->method('getAllVisibleItems')
            ->willReturn($itemsArray);

        $itemsArray[0]->expects($this->once())
            ->method('getProductType')
            ->willReturn('configurable');

        $itemsArray[0]->expects($this->once())
            ->method('getDiscountAmount')
            ->willReturn($expectedPayload['discountAmount']);

        $this->productMock->expects($this->atLeastOnce())
            ->method('getPrice')
            ->willReturn($productPrice = $expectedPayload['lineItems'][0]['unitPrice']);

        $this->urlFinderMock->expects($this->once())
            ->method('fetchFor')
            ->willReturn($expectedPayload['lineItems'][0]['productUrl']);

        $this->imageFinderMock->expects($this->once())
            ->method('getCartImageUrl')
            ->willReturn($expectedPayload['lineItems'][0]['imageUrl']);

        $this->productRepositoryMock->expects($this->atLeastOnce())
            ->method('get')
            ->willReturn($this->productMock);

        $itemsArray[0]->expects($this->once())
            ->method('getTaxPercent')
            ->willReturn(20);

        $itemsArray[0]->expects($this->atLeastOnce())
            ->method('getSku')
            ->willReturn($expectedPayload['lineItems'][0]['sku']);

        $itemsArray[0]->expects($this->exactly(2))
            ->method('getName')
            ->willReturn($expectedPayload['lineItems'][0]['name']);

        $itemsArray[0]->expects($this->once())
            ->method('getBasePrice')
            ->willReturn($itemBasePrice = $expectedPayload['lineItems'][0]['salePrice']);

        $itemsArray[0]->expects($this->once())
            ->method('getPriceInclTax')
            ->willReturn($expectedPayload['lineItems'][0]['salePrice_incl_tax']);

        $itemsArray[0]->expects($this->once())
            ->method('getRowTotal')
            ->willReturn($expectedPayload['lineItems'][0]['totalPrice']);

        $itemsArray[0]->expects($this->once())
            ->method('getRowTotalInclTax')
            ->willReturn($expectedPayload['lineItems'][0]['totalPrice_incl_tax']);

        $itemsArray[0]->expects($this->once())
            ->method('getQty')
            ->willReturn($expectedPayload['lineItems'][0]['quantity']);

        $matcher = $this->exactly(2);
        $this->priceCurrencyInterfaceMock
            ->expects($this->atLeast(2))
            ->method('convertAndRound')
            ->willReturnCallback(function () use ($matcher, $productPrice, $itemBasePrice, $expectedPayload) {
                return match ($matcher->numberOfInvocations()) {
                    1 => [$productPrice, $this->storeId, $expectedPayload['currency']],
                    2 => [$itemBasePrice, $this->storeId, $expectedPayload['currency']]
                };
            })
            ->willReturnOnConsecutiveCalls(
                $productPrice,
                $itemBasePrice
            );

        // Client API call
        $this->abstractResourceMock->expects($this->once())
            ->method('createOrUpdateContactCollectionRecord')
            ->willReturn(json_encode($this->getMockPayload()));

        $this->class->send($this->quoteMock, $this->storeId);
    }

    public function testThatTotalPriceIsCorrectSumRegardlessOfSale()
    {
        $expectedPayload = $this->getMockPayload();
        $salePrice = $expectedPayload['lineItems'][0]['salePrice'];
        $quantity = $expectedPayload['lineItems'][0]['quantity'];
        $totalPrice = $expectedPayload['lineItems'][0]['totalPrice'];

        /*
         * totalPrice is always salePrice * quantity,
         * because salePrice = unitPrice if there is no special price set.
         */
        $this->assertTrue($totalPrice ===  $salePrice * $quantity);
    }

    private function setStoreMock()
    {
        $this->storeManagerInterfaceMock->expects($this->atLeastOnce())
            ->method('getStore')
            ->with($this->storeId)
            ->willReturn($this->storeMock);

        $this->storeMock->expects($this->atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn($this->websiteId);
    }

    /*
     * getMockPayload
     * This dummy array intentionally leaves numbers unformatted as returned by the methods in test.
     */
    private function getMockPayload()
    {
        return [
            "cartId" => "12345",
            "cartUrl" => "https://magentostore.com/cart/12345",
            "createdDate" => "2018-03-31T18:53:28+00:00",
            "modifiedDate" => "2018-03-31T19:53:28+00:00",
            "currency" => "GBP",
            "subTotal" => 98.4,
            "subtotal_incl_tax" => 105.40,
            "discountAmount" => 8.4,
            "taxAmount" => 12.34,
            "shipping" => 11.43,
            "grandTotal" => 90,
            "lineItems" => [
                [
                    "sku" => "PRODUCT-SKU",
                    "imageUrl" => "https://magentostore.com/catalog/product/image.jpg",
                    "productUrl" => "https://magentostore.com/product/PRODUCT-SKU",
                    "name" => "Test Product",
                    "unitPrice" => 49.2,
                    "unitPrice_incl_tax" => 59.04,
                    "quantity" => "2",
                    "salePrice" => 46.15,
                    "salePrice_incl_tax" => 50.25,
                    "totalPrice" => 92.3,
                    "totalPrice_incl_tax" => 110.76
                ]
            ],
            "cartPhase" => "ORDER_PENDING"
        ];
    }
}
