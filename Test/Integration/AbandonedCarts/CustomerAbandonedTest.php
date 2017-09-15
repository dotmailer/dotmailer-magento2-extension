<?php

namespace Dotdigitalgroup\Email\Test\Integration\AbandonedCarts;

/**
For AC1 trigger from updated_at on quote
- Query new AC table for quote id
- If doesn't exist, send
- Insert AC table, with quote_updated_at, product ids, count, ac_number, customer_id
- If it does exist
- If product count and ids have changed
- Send AC1
- Update AC table, with updated_at, product ids, count, ac_number
- else
- Continue
 *
 * @magentoDBIsolation disabled
 */
class CustomerAbandonedTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var object
     */
    public $objectManager;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }


    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_customer.php
     * @magentoConfigFixture default_store abandoned_carts/customers/enabled_1 1
     * @magentoConfigFixture default_store abandoned_carts/customers/send_after_1 0
     * @magentoConfigFixture default_store abandoned_carts/customers/campaign_1 1234
     *
     * customer email customer@example.com, customer id 1
     */
    public function testCustomerAbandonedCartOne()
    {
        //create a quote for customer for AC 1
        $quoteId = 1;
        $emailQuote = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Sales\Quote::class);
        $cronTrigger = $this->objectManager->create(\Dotdigitalgroup\Email\Model\Cron::class);
        $quote = $this->objectManager->create(\Magento\Quote\Model\Quote::class);
        $quote = $quote->loadActive($quoteId);
        $quoteCollection = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
        $campaignCollection = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Campaign\Collection::class
        );

        $result = $emailQuote->proccessAbandonedCarts();

        $storeId = 1;
        $this->assertEquals($result[$storeId]['firstCustomer'], '1', 'No Quotes found for store : ' . $storeId);

        $this->assertContains($quote->getId(), $campaignCollection->getAllIds(), 'Campaing missing for current quote');
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/username dummyusername
     * @magentoConfigFixture default_store connector_api_credentials/api/password dummypassword
     * @magentoConfigFixture default_store connector_automation/visitor_automation/first_order_automation 123
     *
     * @return null
     */
    public function testFirstCustomerAutomation()
    {

        $order = $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId($this->orderIncrementId);
        $orderEmail = $order->getCustomerEmail();
        //set new state and status for order
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_NEW));
        $order->setCustomerId(1);
        $order->save();

        $automation = $this->objectManager->create(
            \Dotdigitalgroup\Email\Model\ResourceModel\Automation\Collection::class
        );
        $automation->addFieldToFilter('email', $orderEmail);
        $automation->addFieldToFilter('automation_type', 'first_order_automation');

        $customerOrders = $this->objectManager->create(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $customerOrders->addFieldToFilter('customer_email', $orderEmail);
        //update to new state
        $this->assertEquals('new', $order->getState(), 'order state not new');

        $order = $this->createInvoice($order);
        //check order is processing
        $this->assertEquals('processing', $order->getState(), 'fail to create invoice for the order ');
        //order save should not create duplicate automation enrollment
        $this->assertEquals(1, $automation->getSize(), 'duplicate automation for first order');
    }

}
