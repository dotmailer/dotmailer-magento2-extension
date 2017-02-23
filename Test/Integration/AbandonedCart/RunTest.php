<?php

namespace Dotdigitalgroup\Email\Test\Integration\Sales;

use Magento\Quote\Model\Quote;
use Magento\TestFramework\ObjectManager;

/**
 * @magentoDBIsolation disabled
 */
class RunTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    public $objectManager;

    /**
     * @var array
     */
    public $createdQuotes = [];

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\Collection
     */
    public $quoteCollection;

    public function setUp()
    {
        $this->objectManager = ObjectManager::getInstance();

    }

    public function tearDown()
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->objectManager->create('Magento\Quote\Model\Quote');

        foreach ($this->createdQuotes as $quoteId) {
            $quote->load($quoteId);
            $quote->delete();
        }
    }

    /**
     * @magentoFixture Magento/Store/_files/core_second_third_fixturestore.php
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_1 1
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_3 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_1 15
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_3 1
     * magentoConfigFixture general/locale/timezone Australia/Melbourne
     */
    public function test_can_find_guest_abandoned_carts()
    {
        $quote = $this->createQuoteForGuests('15');
        /** @var \Dotdigitalgroup\Email\Model\Sales\Quote  $quote */
        $guestQuote = $this->objectManager->create('Dotdigitalgroup\Email\Model\Sales\Quote')
            ->proccessAbandonedCarts('guests');

        $this->quoteCollection = $guestQuote->quoteCollection->create();
        $this->assertGreaterThanOrEqual(1, $guestQuote->totalGuests);
        $this->assertCollectionContains($quote);
    }

    /**
     * @magentoFixture Magento/Store/_files/core_second_third_fixturestore.php
     * @magentoConfigFixture default_store abandoned_carts/customers/send_after_1 15
     * @magentoConfigFixture default_store abandoned_carts/customers/enabled_1 1
     */
    public function test_can_find_customer_abandoned_carts()
    {
        $quote = $this->createQuoteForCustomer($time = '15');
        $email = $quote->getCustomerEmail();
        /** @var \Dotdigitalgroup\Email\Model\Sales\Quote  $quote */
        $customerQuote = $this->objectManager->create('Dotdigitalgroup\Email\Model\Sales\Quote')
            ->proccessAbandonedCarts('customers');
        $this->quoteCollection = $customerQuote->quoteCollection->create();


        $this->assertEquals(1, $customerQuote->totalCustomers);
        $this->assertCollectionContains($quote);

    }


    /**
     * Create a quote with time interval for the updated_at field.
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
            ->setReservedOrderId('test010101')
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setIsActive(1)
            ->setItemsCount(22)
            ->setCustomerEmail('dummy@gmail.com')
            ->addProduct($product);
        $quote->getPayment()->setMethod('checkmo');
        $quote->setIsMultiShipping('1');
        $quote->collectTotals();

        $now = new \Datetime('now');
        $time = \DateInterval::createFromDateString($time  . ' minutes');
        $quote->setUpdatedAt($now->sub($time));

        $quote->save();

        $this->createdQuotes[] = $quote->getId();
        return $quote;
    }


    protected function createQuoteForCustomer($time)
    {
        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        /** @var $repository \Magento\Customer\Api\CustomerRepositoryInterface */
        $repository = $objectManager->create('Magento\Customer\Api\CustomerRepositoryInterface');
        $customer = $objectManager->create('Magento\Customer\Model\Customer');

        /** @var \Magento\Customer\Model\Customer $customer */
        $customer->setWebsiteId(1)
            ->setId(1)
            ->setEmail('customer@example.com')
            ->setPassword('password')
            ->setGroupId(1)
            ->setStoreId(1)
            ->setIsActive(1)
            ->setPrefix('Mr.')
            ->setFirstname('John')
            ->setMiddlename('A')
            ->setLastname('Smith')
            ->setSuffix('Esq.')
            ->setDefaultBilling(1)
            ->setDefaultShipping(1)
            ->setTaxvat('12')
            ->setGender(0);

        $customer->isObjectNew(true);
        $customer->save();


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

        /** @var $quote \Magento\Quote\Model\Quote */
        $quote = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create('Magento\Quote\Model\Quote');
        $quote->load('test01', 'reserved_order_id');
        /** @var \Magento\Customer\Api\CustomerRepositoryInterface $customer */
        $customerRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
            ->create('Magento\Customer\Api\CustomerRepositoryInterface');
        $customer = $customerRepository->getById(1);
        $quote->setCustomer($customer)
            ->setCustomerIsGuest(false)
            ->setStoreId($store->getId())
            ->setReservedOrderId('test020202')
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setIsActive(1)
            ->setItemsCount(15)
            ->addProduct($product);
        $now = new \Datetime('now');
        $time = \DateInterval::createFromDateString($time  . ' minutes');
        $quote->setUpdatedAt($now->sub($time));

        $quote->save();

        $this->createdQuotes[] = $quote->getId();

        return $quote;
    }

    private function assertCollectionContains( $expected)
    {
        $message = sprintf('The quote with ID "%s" is not contained in the quote collection', $expected->getId());
        $this->assertContains($expected->getId(), $this->quoteCollection->getAllIds(), $message);

    }
}
