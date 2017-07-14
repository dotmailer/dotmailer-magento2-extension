<?php

namespace Dotdigitalgroup\Email\Test\Integration\Automation;

use Magento\TestFramework\ObjectManager;

/**
 * Class FirstOrderTest
 * @package Dotdigitalgroup\Email\Test\Integration\Automation
 *
 * @magentoDBIsolation disabled
 */
class FirstOrderTest extends \PHPUnit_Framework_TestCase
{

    public $objectManager;
    public $orderIncrementId;

    public function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->orderIncrementId = '100000001';
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_with_customer.php
     * @magentoConfigFixture default_store connector_api_credentials/api/enabled 1
     * @magentoConfigFixture default_store connector_api_credentials/api/username dummyusername
     * @magentoConfigFixture default_store connector_api_credentials/api/password dummypassword
     * @magentoConfigFixture default_store connector_automation/visitor_automation/first_order_automation 123
     */
    public function test_first_customer_automation() //@codingStandardsIgnoreLine
    {
        $customer = $this->objectManager->create(\Magento\Customer\Model\Customer::class);

        $order = $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId($this->orderIncrementId);
        $orderEmail = $order->getCustomerEmail();
        //set new state and status for order
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_NEW));
        $order->setCustomerId(1);
        $order->save();

        $automation = $this->objectManager
            ->create(\Dotdigitalgroup\Email\Model\ResourceModel\Automation\Collection::class);
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

    /**
     * @param $order
     * @return mixed
     */
    public function createInvoice($order)
    {
        $orderService = \Magento\TestFramework\ObjectManager::getInstance()->create(
            'Magento\Sales\Api\InvoiceManagementInterface'
        );
        $invoice = $orderService->prepareInvoice($order);
        $invoice->register();
        $order = $invoice->getOrder();
        $order->setIsInProcess(true);

        $order->save();

        return $order;
    }
}
