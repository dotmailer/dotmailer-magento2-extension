<?php

namespace Dotdigitalgroup\Email\Test\Integration\Sales;

use Magento\Quote\Model\Quote;
use Magento\TestFramework\ObjectManager;

/**
 * @package Dotdigitalgroup\Email\Controller\Customer
 * @magentoDBIsolation disabled
 */
class RunTest extends \Magento\TestFramework\TestCase\AbstractController
{

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    public $objectManager;
    /**
     * @var
     */
    public $quoteFactory;

    public function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();

    }

    /**
     * @magentoFixture Magento/Store/_files/core_second_third_fixturestore.php
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_1 1
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_3 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_1 15
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_3 1
     * @magentoConfigFixture general/locale/timezone Australia/Melbourne
     */
    public function test_can_find_guest_abandoned_carts()
    {
        $this->createQuoteForGuests('15');
        /** @var \Dotdigitalgroup\Email\Model\Sales\Quote  $connectorQuote */
        $connectorQuote = $this->objectManager->create('Dotdigitalgroup\Email\Model\Sales\Quote')
            ->proccessAbandonedCarts('guests');

        $this->assertEquals('3', $connectorQuote);
    }


    /**
     * Create a quote with time interval for the updatedat field.
     *
     * @param $time
     * @return mixed
     */
    protected function createQuoteForGuests($time)
    {
        \Magento\TestFramework\Helper\Bootstrap::getInstance()->loadArea('frontend');
        $product = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create('Magento\Catalog\Model\Product');
        $product->setTypeId('simple')
            ->setId(1)
            ->setAttributeSetId(4)
            ->setName('Simple Product')
            ->setSku('simple')
            ->setPrice(10)
            ->setTaxClassId(0)
            ->setMetaTitle('meta title')
            ->setMetaKeyword('meta keyword')
            ->setMetaDescription('meta description')
            ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
            ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->setStockData(['qty' => 100, 'is_in_stock' => 1,])
            ->save();

        $productRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create('Magento\Catalog\Api\ProductRepositoryInterface');
        $product = $productRepository->get('simple');
        $addressData = [
            'region' => 'CA',
            'postcode' => '11111',
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'street' => 'street',
            'city' => 'Los Angeles',
            'email' => 'admin@example.com',
            'telephone' => '11111111',
            'country_id' => 'US'
        ];
        $billingAddress = $this->objectManager->create(
            'Magento\Quote\Model\Quote\Address',
            ['data' => $addressData]
        );
        $billingAddress->setAddressType('billing');
        $shippingAddress = clone $billingAddress;
        $shippingAddress->setId(null)->setAddressType('shipping');
        $store = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->get('Magento\Store\Model\StoreManagerInterface')
            ->getStore();

        $quote = ObjectManager::getInstance()->create(Quote::class);
        $quote->setCustomerIsGuest(true)
            ->setStoreId($store->getId())
            ->setReservedOrderId('test01')
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setIsActive(1)
            ->setItemsCount(22)
            ->addProduct($product);
        $quote->getPayment()->setMethod('checkmo');
        $quote->setIsMultiShipping('1');
        $quote->collectTotals();

        $now = new \Datetime('now');
        $time = \DateInterval::createFromDateString($time  . ' minutes');
        $quote->setUpdatedAt($now->sub($time));

        $quote->save();

        return $quote;
    }
}
