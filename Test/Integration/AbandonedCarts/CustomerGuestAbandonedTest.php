<?php

namespace Dotdigitalgroup\Email\Test\Integration\AbandonedCarts;

include __DIR__ . DIRECTORY_SEPARATOR . '_files/abandonedCartOneHour.php';
/**
 * @magentoDBIsolation disabled
 *
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
        $emailQuote = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Sales\Quote::class);
        $customerAbandonedCart = $this->quote->loadActive($quoteId);
        /**
         * run the cron for abandoned carts
         *
         * abandoned cart name  => number of carts
         */
        $result = $emailQuote->proccessAbandonedCarts();

        $this->assertEquals(1, $result[$storeId]['firstCustomer'], 'No Quotes found for store : ' . $storeId);
        $this->assertCampaignCreatedFor($customerAbandonedCart);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoConfigFixture default_store abandoned_carts/customers/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/customers/send_after_2 1
     * @magentoConfigFixture default_store abandoned_carts/customers/campaign_2 1234
     */
    public function testExistingCustomerAbandonedCart()
    {
        $emailQuote = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Sales\Quote::class);
        $abandonedCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\Collection::class
        )->getFirstItem();
        $campaignCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\Collection::class
        );

        $quote = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
        $allIds = $campaignCollection->getAllIds();
        $result = $emailQuote->proccessAbandonedCarts();
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
        $quote = $quoteCollection->addFieldToFilter('customer_email', $email)
            ->getFirstItem();
        $storeId = $quote->getStoreId();
        $emailQuote = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Sales\Quote::class);

        $result = $emailQuote->proccessAbandonedCarts();

        $this->assertEquals(1, $result[$storeId]['firstGuest'], 'Abandoned cart not found for guest');
        $this->assertCampaignCreatedFor($quote);
    }

    /**
     * @param $customerAbandonedCart
     */
    private function assertCampaignCreatedFor($customerAbandonedCart)
    {
        $campaignCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\Collection::class
        );
        $cartId = $customerAbandonedCart->getId();
        $this->assertContains(
            $cartId,
            $campaignCollection->getAllIds(),
            'Campaing missing for current quote : ' . $cartId
        );
    }

}
