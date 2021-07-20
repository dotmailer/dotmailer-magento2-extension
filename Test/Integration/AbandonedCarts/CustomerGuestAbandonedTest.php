<?php

namespace Dotdigitalgroup\Email\Test\Integration\AbandonedCarts;

if (!class_exists('\Magento\Catalog\Api\Data\ProductExtensionInterfaceFactory')) {
    require __DIR__ . '/../_files/product_extension_interface_hacktory.php';
}

use Dotdigitalgroup\Email\Model\Abandoned;
use Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\ResourceModel\Abandoned as AbandonedResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\Collection as AbandonedCollection;
use Dotdigitalgroup\Email\Model\Sales\Quote;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Quote\Model\ResourceModel\Quote\Collection;

class CustomerGuestAbandonedTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    public $quote;

    /**
     * @var \Dotdigitalgroup\Email\Test\Integration\AbandonedCarts\Fixture
     */
    public $fixture;

    /**
     * @return void
     */
    public function setUp() :void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->setApiConfigFlags();

        $this->quote = $this->objectManager->create(\Magento\Quote\Model\Quote::class);
        $this->fixture = $this->objectManager->create(Fixture::class);
        $this->loadCustomerQuoteTextureFile();

        $cartInsightMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cartInsightMock->method('send')->willReturn(true);
        $this->objectManager->addSharedInstance($cartInsightMock, Data::class);

        $mockClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(get_class_methods(Client::class))
            ->getMock();
        $mockClient->method('setApiUsername')
            ->willReturn(new class() {
                public function setApiPassword($password)
                {
                }
            });
        $mockClient->method('getContactByEmail')
            ->willReturn((object) [
                'id' => 1234566,
                'status' => "Subscribed",
            ]);

        $clientFactoryClass = 'Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory';
        $mockClientFactory = $this->getMockBuilder($clientFactoryClass)
            ->disableOriginalConstructor()
            ->getMock();
        $mockClientFactory->method('create')->willReturn($mockClient);

        // share a pre-generated data helper with the mock factory
        $this->instantiateDataHelper([
            $clientFactoryClass => $mockClientFactory,
        ]);
    }

    public function tearDown() :void
    {
        $abandonedCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\Collection::class
        );
        $abandonedCollection->walk('delete');
        $quoteCollection = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
        $quoteCollection->walk('delete');
    }

    /**
     * @magentoConfigFixture default_store connector_configuration/abandoned_carts/allow_non_subscribers 1
     * @magentoConfigFixture default_store abandoned_carts/customers/enabled_1 1
     * @magentoConfigFixture default_store abandoned_carts/customers/send_after_1 0
     * @magentoConfigFixture default_store abandoned_carts/customers/campaign_1 1234
     */
    public function testCustomerAbandonedCartOne()
    {
        $numExpectedAC = 1;
        //create a quote for customer for AC 1
        $quoteCollection = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
        $quote = $quoteCollection->getFirstItem();
        $quoteId = $quote->getId();
        $storeId = $quote->getStoreId();
        $this->quote->loadActive($quoteId);

        /**
         * run the cron for abandoned carts
         *
         * abandoned cart name  => number of carts
         * @var Quote $emailQuote
         */
        $emailQuote = $this->objectManager->create(Quote::class);
        $result = $emailQuote->processAbandonedCarts();

        $this->assertEquals(
            $numExpectedAC,
            $result[$storeId]['firstCustomer'],
            'Abandoned cart was not found for store : ' . $storeId . ', quote id : ' . $quoteId
        );
    }

    /**
     * @magentoConfigFixture default_store abandoned_carts/customers/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/customers/send_after_2 60
     * @magentoConfigFixture default_store abandoned_carts/customers/send_after_1 15
     * @magentoConfigFixture default_store abandoned_carts/customers/campaign_2 1234
     * @magentoConfigFixture default_store connector/api/endpoint https://r1-api.dotmailer.com
     * @magentoConfigFixture default_store connector_configuration/abandoned_carts/allow_non_subscribers 1
     */
    public function testExistingCustomerAbandonedCart()
    {
        $sendAfter = '1';
        $abandoned = $this->createExistingAbandonedCart($sendAfter);
        $quoteId = $abandoned->getQuoteId();

        $emailQuote = $this->objectManager->create(Quote::class);
        $this->createEmailQuoteMockInstance();
        $emailQuote->processAbandonedCarts();

        $abandonedCart = $this->objectManager->create(Abandoned::class)
            ->loadByQuoteId($quoteId);

        $this->assertEquals(
            $abandonedCart->getQuoteId(),
            $quoteId,
            'Abandoned Cart not found, quote_id :  ' . $quoteId
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_bundle.php
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_1 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_1 0
     * @magentoConfigFixture default_store abandoned_carts/guests/campaign_1 1234
     * @magentoConfigFixture default_store connector/api/endpoint https://r1-api.dotmailer.com
     * @magentoConfigFixture default_store connector_configuration/abandoned_carts/allow_non_subscribers 1
     */
    public function testGuestAbandonedCartOne()
    {
        $email = 'test@test.magento.com';
        $quoteCollection = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
        $quoteCollection->addFieldToFilter('customer_is_guest', true)
            ->addFieldToFilter('customer_email', $email)
            ->getFirstItem();
        $emailQuote = $this->objectManager->create(Quote::class);
        $emailQuote->processAbandonedCarts();
    }

    /**
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_3 2
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_1 15
     * @magentoConfigFixture default_store abandoned_carts/guests/campaign_2 1234
     * @magentoConfigFixture default_store connector_configuration/abandoned_carts/allow_non_subscribers 1
     */
    public function testExistingAbandonedCartGuest()
    {
        $abandoned = $this->createExistingAbandonedCart(1, 'dotguesttest02', [], 1);
        $storeId = $abandoned->getStoreId();

        /** @var Quote $emailQuote */
        $emailQuote = $this->objectManager->create(Quote::class);
        $result = $emailQuote->processAbandonedCarts();

        $this->assertEquals(1, $result[$storeId]['secondGuest']);
    }

    /**
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_3 2
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_1 15
     * @magentoConfigFixture default_store abandoned_carts/guests/campaign_2 1234
     * @magentoConfigFixture default_store connector_configuration/abandoned_carts/allow_non_subscribers 1
     */
    public function testExistingGuestAbandonedCartItemsChanged()
    {
        $abandoned = $this->createExistingAbandonedCart(1, 'dottest02', [45,34], 1, 0);

        $quoteId = $abandoned->getQuoteId();
        $storeId = $abandoned->getStoreId();

        /** @var Quote $emailQuote */
        $emailQuote = $this->objectManager->create(Quote::class);

        //run the abandoned carts
        $result = $emailQuote->processAbandonedCarts();

        //try to load the email abandoned by quote id what it should be removed not sent
        $processedAbandonedCart = $this->objectManager->create(Abandoned::class)
            ->loadByQuoteId($quoteId);

        $this->assertNull($processedAbandonedCart->getId(), 'AC was sent but it should not be sent!');
        $this->assertEquals(0, $result[$storeId]['secondGuest']);
    }

    /**
     * @param $hour
     * @param string $reservedOrderId
     * @param array $itemIds
     * @param int $abandonedCartNumber
     * @return Abandoned
     * @throws \Exception
     */
    private function createExistingAbandonedCart(
        $hour,
        $reservedOrderId = 'dottet01',
        $itemIds = [],
        $abandonedCartNumber = 1,
        $updateQuoteItemCount = null
    ) {
        /** @var Collection $quoteCollection */
        $quoteCollection = $this->objectManager->create(Collection::class);
        $quote = $quoteCollection->getFirstItem();
        $quote->setCustomerId(null);
        $quote->setCustomerIsGuest('1');

        if (!$itemIds) {
            foreach ($quote->getItemsCollection() as $item) {
                $itemIds[] = $item->getProductId();
            }
        }
        if (!is_null($updateQuoteItemCount)) {
            $quote->setItemsCount($updateQuoteItemCount);
        }
        $quote->save();

        $quoteUpdatedAt = new \DateTime('now', new \DateTimezone('UTC'));
        $quoteUpdatedAt->sub(\DateInterval::createFromDateString($hour . ' hours + 1 minutes'));

        /** @var Abandoned $abandonedModel */
        $abandonedModel = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Abandoned::class);
        $abandonedModel->setQuoteId($quote->getId())
            ->setIsActive(1)
            ->setItemsCount(count($itemIds))
            ->setQuoteId($quote->getId())
            ->setItemsIds(implode(',', $itemIds))
            ->setCreatedAt($updated = (new \DateTime('now', new \DateTimezone('UTC')))->getTimestamp())
            ->setAbandonedCartNumber($abandonedCartNumber)
            ->setStoreId($quote->getStoreId())
            ->setQuoteUpdatedAt($quoteUpdatedAt)
            ->setStatus('Sent')
            ->setCustomerId($quote->getCustomerId())
            ->setEmail($quote->getCustomerEmail())
            ->setUpdatedAt($updated);

        $resourceAbandoned = $this->objectManager->create(AbandonedResource::class);
        $resourceAbandoned->save($abandonedModel);

        return $abandonedModel;
    }

    private function loadCustomerQuoteTextureFile()
    {
        include __DIR__ . '/../_files/customer.php';
        $this->fixture->createQuote($this->objectManager, 1, 'dottest01', true);
    }

    private function loadGuestQuoteTextureFile()
    {
        $this->fixture->createQuote($this->objectManager, 1, 'dotguesttest02');
    }

    private function loadQuestQuoteTextureFile()
    {
        $this->fixture->createQuote($this->objectManager, 0, 'dottest02');
    }

    private function createEmailQuoteMockInstance()
    {
        $abandonedCollectionMock = $this->getMockBuilder(AbandonedCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $abandonedCollectionMock->method('getAbandonedCartsForStore')->willReturn($abandonedCollectionMock);
        $abandonedCollectionMock->method('getColumnValues')->willReturn([1,2,3]);

        $this->objectManager->addSharedInstance(
            $abandonedCollectionMock,
            \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\Collection::class
        );
    }
}
