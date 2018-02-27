<?php

namespace Dotdigitalgroup\Email\Test\Integration\AbandonedCarts;

use Dotdigitalgroup\Email\Model\Abandoned;
use Dotdigitalgroup\Email\Model\ResourceModel\Abandoned as AbandonedResource;
use Dotdigitalgroup\Email\Model\Sales\Quote;

/**
 * @magentoDBIsolation disabled
 */
class CustomerGuestAbandonedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var object
     */
    public $objectManager;

    /**
     * @var
     */
    public $quote;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->quote = $this->objectManager->create(\Magento\Quote\Model\Quote::class);
    }

    public function tearDown()
    {
        $abandonedCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\Collection::class
        );
        foreach ($abandonedCollection as $abandoned) {
            $abandoned->delete();
        }
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoConfigFixture default_store abandoned_carts/customers/enabled_1 1
     * @magentoConfigFixture default_store abandoned_carts/customers/send_after_1 0
     * @magentoConfigFixture default_store abandoned_carts/customers/campaign_1 1234
     *
     * customer email customer@example.com, customerid 1, storeid 1
     */
    public function testCustomerAbandonedCartOne()
    {
        //create a quote for customer for AC 1
        $quoteCollection = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
        $quote = $quoteCollection->getFirstItem();
        $quoteId = $quote->getId();
        $storeId = $quote->getStoreId();
        $emailQuote = $this->objectManager->create(Quote::class);
        $customerAbandonedCart = $this->quote->loadActive($quoteId);
        /**
         * run the cron for abandoned carts
         *
         * abandoned cart name  => number of carts
         */
        $result = $emailQuote->proccessAbandonedCarts();

        $this->assertEquals(1, $result[$storeId]['firstCustomer'], 'No Quotes found for store : ' . $storeId);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoConfigFixture default_store abandoned_carts/customers/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/customers/send_after_2 1
     * @magentoConfigFixture default_store abandoned_carts/customers/campaign_2 1234
     */
    public function testExistingCustomerAbandonedCart()
    {
        $sendAfter = '1';
        $abandoned = $this->createAbandonedCart($sendAfter);
        $quoteId = $abandoned->getQuoteId();
        $emailQuote = $this->objectManager->create(Quote::class);
        $emailQuoteMock = $this->getMock(Quote::class, [], [], '', false);
        $emailQuoteMock->method('getAbandonedCartsForStore')->willReturn([]);
        $abandonedCollectionMock = $this->getMockBuilder(
            \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\Collection::class
        )->disableOriginalConstructor()
            ->getMock();
        $abandonedCollectionMock->method('getColumnValues')->willReturn([1,2,3]);
        $this->objectManager->addSharedInstance(
            $abandonedCollectionMock,
            \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\Collection::class
        );
        $this->objectManager->addSharedInstance($emailQuoteMock, Quote::class);

        $emailQuote->proccessAbandonedCarts();

        $abandonedCart = $this->objectManager->create(Abandoned::class)
            ->loadByQuoteId($quoteId);

        $this->assertEquals($abandonedCart->getQuoteId(), $quoteId, 'Abandoned Cart not found');
    }


    /**
     * @magentoDBIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote_with_bundle.php
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_1 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_1 0
     * @magentoConfigFixture default_store abandoned_carts/guests/campaign_1 1234
     */
    public function testGuestAbandonedCartOne()
    {
        $email = 'test@test.magento.com';
        $quoteCollection = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
        $quote = $quoteCollection->addFieldToFilter('customer_is_guest', true)
            ->addFieldToFilter('customer_email', $email)
            ->getFirstItem();
        $emailQuote = $this->objectManager->create(Quote::class);
        $emailQuote->proccessAbandonedCarts();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/campaign_2 1234
     */
    public function testExistingGuestAbandonedCart()
    {
        $abandonedResource = $this->objectManager->create(AbandonedResource::class);
        $abandoned = $this->createAbandonedCart($hour = 1);
        $abandoned->setCustomerId(null)
            ->setItemsCount(3)
            ->setItemsIds('2,3,4');
        $abandonedResource->save($abandoned);

        $quoteId = $abandoned->getQuoteId();
        $storeId = $abandoned->getStoreId();
        $abandonedCartNumber = $abandoned->getAbandonedCartNumber();

        $emailQuote = $this->objectManager->create(Quote::class);
        $quoteMock = $this->getMock(Quote::class, [], [], '', false);

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

        $result = $emailQuote->proccessAbandonedCarts();

        $proccessedAbandonedCart = $this->objectManager->create(Abandoned::class)
            ->loadByQuoteId($quoteId)->getAbandonedCartNumber();
        $this->assertEquals(++$abandonedCartNumber, $proccessedAbandonedCart);
        $this->assertEquals(1, $result[$storeId]['secondGuest']);
    }

    /**
     * @param $hour
     * @return mixed
     */
    private function createAbandonedCart($hour)
    {
        $abandonedModel = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Abandoned::class);
        $quoteUpdatedAt = new \DateTime('now', new \DateTimezone('UTC'));
        $quoteUpdatedAt->sub(\DateInterval::createFromDateString($hour . ' hours + 1 minutes'));
        $quote = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class)
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
}
