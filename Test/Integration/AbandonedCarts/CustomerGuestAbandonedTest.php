<?php

namespace Dotdigitalgroup\Email\Test\Integration\AbandonedCarts;

use Dotdigitalgroup\Email\Model\Abandoned;
use Dotdigitalgroup\Email\Model\ResourceModel\Abandoned as AbandonedResource;
use Dotdigitalgroup\Email\Model\Sales\Quote;

/**
 * Class CustomerGuestAbandonedTest
 * @package Dotdigitalgroup\Email\Test\Integration\AbandonedCarts
 */
class CustomerGuestAbandonedTest extends \PHPUnit\Framework\TestCase
{
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
    public function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->quote = $this->objectManager->create(\Magento\Quote\Model\Quote::class);
        $this->fixture = $this->objectManager->create(Fixture::class);
        $this->loadCustomerQuoteTextureFile();
    }

    public function tearDown()
    {
        $abandonedCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\Collection::class
        );
        $abandonedCollection->walk('delete');
        $quoteCollection = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
        $quoteCollection->walk('delete');
    }

    /**
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
        $emailQuote = $this->objectManager->create(Quote::class);
        $this->quote->loadActive($quoteId);
        /**
         * run the cron for abandoned carts
         *
         * abandoned cart name  => number of carts
         */
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
     * @magentoDBIsolation enabled
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_3 2
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_1 15
     * @magentoConfigFixture default_store abandoned_carts/guests/campaign_2 1234
     */
    public function testExistingGuestAbandonedCart()
    {
        $this->loadGuestQuoteTextureFile();
        $this->createEmailQuoteMockInstance();
        $abandonedResource = $this->objectManager->create(AbandonedResource::class);
        $abandoned = $this->createExistingAbandonedCart(1, 'dotguesttest02');
        $abandoned->setItemsCount(1)
            ->setItemsIds('1');
        $abandonedResource->save($abandoned);
        $storeId = $abandoned->getStoreId();

        $emailQuote = $this->objectManager->create(Quote::class);
        $result = $emailQuote->processAbandonedCarts();

        $this->assertEquals(1, $result[$storeId]['secondGuest']);
    }

    /**
     * @magentoDBIsolation enabled
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_3 2
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_1 15
     * @magentoConfigFixture default_store abandoned_carts/guests/campaign_2 1234
     */
    public function testExistingGuestAbandonedCartItemsChanged()
    {
        $this->loadQuestQuoteTextureFile();

        $abandonedResource = $this->objectManager->create(AbandonedResource::class);
        $abandoned = $this->createExistingAbandonedCart(1, 'dottest02');
        $abandoned->setCustomerId(null)
            ->setItemsCount(10)
            ->setItemsIds('1,2,3');
        $abandonedResource->save($abandoned);

        $quoteId = $abandoned->getQuoteId();
        $storeId = $abandoned->getStoreId();

        $emailQuote = $this->objectManager->create(Quote::class);
        //create a mock and add a instance to shared env
        $this->createEmailQuoteMockInstance();

        //run the abandoned carts
        $result = $emailQuote->processAbandonedCarts();
        //try to load the email abandoned by quote id what it should be removed not sent
        $proccessedAbandonedCart = $this->objectManager->create(Abandoned::class)
            ->loadByQuoteId($quoteId);

        $this->assertNull($proccessedAbandonedCart->getId(), 'AC was sent but it should not be sent!');
        $this->assertEquals(0, $result[$storeId]['secondGuest']);
    }
    /**
     * @param int $hour
     * @return \Dotdigitalgroup\Email\Model\Abandoned
     */
    private function createExistingAbandonedCart($hour, $reservedOrderId = 'dottet01')
    {
        $abandonedModel = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Abandoned::class);
        $quoteUpdatedAt = new \DateTime('now', new \DateTimezone('UTC'));
        $quoteUpdatedAt->sub(\DateInterval::createFromDateString($hour . ' hours + 1 minutes'));
        $quote = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class)
            ->addFieldToFilter('reserved_order_id', $reservedOrderId)
            ->getFirstItem();
        $abandonedModel->setQuoteId($quote->getId())
            ->setIsActive(1)
            ->setItemsCount(2)
            ->setItemsIds('2,3')
            ->setCreatedAt(time())
            ->setAbandonedCartNumber(1)
            ->setStoreId($quote->getStoreId())
            ->setQuoteUpdatedAt($quoteUpdatedAt)
            ->setEmail($quote->getCustomerEmail())
            ->setCustomerId($quote->getCustomerId())
            ->setUpdatedAt(time());

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
        $quoteMock = $this->getMockForAbstractClass(
            Quote::class,
            [],
            '',
            false,
            false,
            true,
            ['getAbandonedCartsForStore']
        );

        $abandonedCollectionMock = $this->getMockBuilder(
            \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\Collection::class
        )->disableOriginalConstructor()
            ->getMock();

        $quoteMock->method('getAbandonedCartsForStore')->willReturn([]);
        $abandonedCollectionMock->method('getColumnValues')->willReturn([1,2,3]);

        $this->objectManager->addSharedInstance(
            $abandonedCollectionMock,
            \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\Collection::class
        );
        $this->objectManager->addSharedInstance($quoteMock, Quote::class);
    }
}
