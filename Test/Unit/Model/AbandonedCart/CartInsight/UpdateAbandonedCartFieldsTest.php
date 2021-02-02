<?php

namespace Dotdigitalgroup\Email\Test\Unit\Model\AbandonedCart\CartInsight;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Logger\Logger;
use Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data as CartInsightData;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Catalog\UrlFinder;
use Dotdigitalgroup\Email\Model\Product\ImageFinder;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Dotdigitalgroup\Email\Model\Product\ImageType\Context\AbandonedCart;

class UpdateAbandonedCartFieldsTest extends TestCase
{
    /**
     * @var Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $helperMock;

    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    private $clientMock;

    /**
     * @var ProductRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepositoryMock;

    /**
     * @var Emulation|\PHPUnit_Framework_MockObject_MockObject
     */
    private $emulationMock;

    /**
     * @var Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productMock;

    /**
     * @var StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerInterfaceMock;

    /**
     * @var DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeMock;

    /**
     * @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteMock;

    /**
     * @var Item|\PHPUnit_Framework_MockObject_MockObject
     */
    private $itemMock;

    /**
     * @var CartInsightData
     */
    private $class;

    /**
     * @var
     */
    private $websiteId = 10;

    /**
     * @var
     */
    private $storeId = 1;

    /**
     * @var Store\PHPUnit_Framework_MockObject_MockObject
     */
    private $storeMock;

    /**
     * @var UrlFinder\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlFinderMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $imageFinderMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerMock;

    /**
     * @var AbandonedCart|\PHPUnit_Framework_MockObject_MockObject
     */
    private $imageTypeMock;

    protected function setUp() :void
    {
        $this->helperMock = $this->createMock(Data::class);
        $this->clientMock = $this->createMock(Client::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->emulationMock = $this->createMock(Emulation::class);
        $this->productMock = $this->createMock(Product::class);
        $this->storeManagerInterfaceMock = $this->createMock(StoreManagerInterface::class);
        $this->storeMock = $this->createMock(Store::class);
        $this->quoteMock = $this->createMock(Quote::class);
        $this->itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProductType', 'getSku', 'getName', 'getQty'])
            ->addMethods(['getDiscountAmount', 'getBasePriceInclTax', 'getRowTotalInclTax'])
            ->getMock();
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->urlFinderMock = $this->createMock(UrlFinder::class);
        $this->imageFinderMock = $this->createMock(ImageFinder::class);
        $this->loggerMock = $this->createMock(Logger::class);
        $this->imageTypeMock = $this->createMock(AbandonedCart::class);

        $this->class = new CartInsightData(
            $this->storeManagerInterfaceMock,
            $this->productRepositoryMock,
            $this->emulationMock,
            $this->helperMock,
            $this->dateTimeMock,
            $this->urlFinderMock,
            $this->imageFinderMock,
            $this->loggerMock,
            $this->imageTypeMock
        );
    }

    public function testSendFunction()
    {
        $this->setStoreMock();

        $this->helperMock->expects($this->once())
            ->method('getWebsiteApiClient')
            ->with($this->websiteId)
            ->willReturn($this->clientMock);

        $expectedPayload = $this->getMockPayload();

        $this->quoteMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($expectedPayload['key']);

        $this->quoteMock->expects($this->atLeastOnce())
            ->method('__call')
            ->withConsecutive(
                [$this->equalTo('getCustomerEmail')],
                [$this->equalTo('getQuoteCurrencyCode')],
                [$this->equalTo('getSubtotal')],
                [$this->equalTo('getGrandTotal')]
            )
            ->willReturnOnConsecutiveCalls(
                $expectedPayload['contactIdentifier'],
                $expectedPayload['json']['currency'],
                $expectedPayload['json']['subTotal'],
                $expectedPayload['json']['grandTotal']
            );

        $this->storeMock->expects($this->once())
            ->method('getUrl')
            ->with(
                'connector/email/getbasket',
                ['quote_id' => $expectedPayload['key']]
            )
            ->willReturn($expectedPayload['json']['cartUrl']);

        // Dates
        $createdAt = "2018-03-31 18:53:28";
        $updatedAt = "2018-03-31 19:53:28";

        $this->quoteMock->expects($this->once())
            ->method('getCreatedAt')
            ->willReturn($createdAt);

        $this->quoteMock->expects($this->once())
            ->method('getUpdatedAt')
            ->willReturn($updatedAt);

        $this->dateTimeMock
            ->method('date')
            ->withConsecutive(
                ['c', $createdAt],
                ['c', $updatedAt]
            )
            ->willReturnOnConsecutiveCalls(
                $expectedPayload['json']['createdDate'],
                $expectedPayload['json']['modifiedDate']
            );

        $addressMock = $this->createMock(Quote\Address::class);
        $this->quoteMock->expects($this->atLeastOnce())
            ->method('getShippingAddress')
            ->willReturn($addressMock);

        $addressMock->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('getTaxAmount'))
            ->willReturn($expectedPayload['json']['taxAmount']);

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
            ->willReturn($expectedPayload['json']['discountAmount']);

        $this->productMock->expects($this->once())
            ->method('getPrice')
            ->willReturn($expectedPayload['json']['lineItems'][0]['unitPrice']);

        $this->urlFinderMock->expects($this->once())
            ->method('fetchFor')
            ->willReturn($expectedPayload['json']['lineItems'][0]['productUrl']);

        $this->imageFinderMock->expects($this->once())
            ->method('getCartImageUrl')
            ->willReturn($expectedPayload['json']['lineItems'][0]['imageUrl']);

        $this->productRepositoryMock->expects($this->atLeastOnce())
            ->method('get')
            ->willReturn($this->productMock);

        $itemsArray[0]->expects($this->atLeastOnce())
            ->method('getSku')
            ->willReturn($expectedPayload['json']['lineItems'][0]['sku']);

        $itemsArray[0]->expects($this->once())
            ->method('getName')
            ->willReturn($expectedPayload['json']['lineItems'][0]['name']);

        $itemsArray[0]->expects($this->once())
            ->method('getBasePriceInclTax')
            ->willReturn($expectedPayload['json']['lineItems'][0]['salePrice']);

        $itemsArray[0]->expects($this->once())
            ->method('getRowTotalInclTax')
            ->willReturn($expectedPayload['json']['lineItems'][0]['totalPrice']);

        $itemsArray[0]->expects($this->once())
            ->method('getQty')
            ->willReturn($expectedPayload['json']['lineItems'][0]['quantity']);

        // Client API call
        $this->clientMock->expects($this->once())
            ->method('postAbandonedCartCartInsight')
            ->will($this->returnCallback(
                function ($payload) use (&$actualPayload) {
                    $actualPayload = $payload;
                }
            ));

        $this->class->send($this->quoteMock, $this->storeId, $this->websiteId);

        $this->assertJsonStringEqualsJsonString(json_encode($expectedPayload), json_encode($actualPayload));
    }

    public function testThatTotalPriceIsCorrectSumRegardlessOfSale()
    {
        $expectedPayload = $this->getMockPayload();
        $salePrice = $expectedPayload['json']['lineItems'][0]['salePrice'];
        $quantity = $expectedPayload['json']['lineItems'][0]['quantity'];
        $totalPrice = $expectedPayload['json']['lineItems'][0]['totalPrice'];

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
            "key" => "12345",
            "contactIdentifier" => "test@emailsim.io",
            "json" => [
                "cartId" => "12345",
                "cartUrl" => "https://magentostore.com/cart/12345",
                "createdDate" => "2018-03-31T18:53:28+00:00",
                "modifiedDate" => "2018-03-31T19:53:28+00:00",
                "currency" => "GBP",
                "subTotal" => 98.4,
                "discountAmount" => 8.4,
                "taxAmount" => 12.34,
                "grandTotal" => 90,
                "lineItems" => [
                    [
                        "sku" => "PRODUCT-SKU",
                        "imageUrl" => "https://magentostore.com/catalog/product/image.jpg",
                        "productUrl" => "https://magentostore.com/product/PRODUCT-SKU",
                        "name" => "Test Product",
                        "unitPrice" => 49.2,
                        "quantity" => "2",
                        "salePrice" => 46.15,
                        "totalPrice" => 92.3
                    ]
                ],
                "cartPhase" => "ORDER_PENDING"
            ]
        ];
    }
}
