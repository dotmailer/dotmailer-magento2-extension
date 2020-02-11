<?php

namespace Dotdigitalgroup\Email\Model;

if (!class_exists('\Magento\Catalog\Api\Data\ProductExtensionInterfaceFactory')) {
    require __DIR__ . '/../_files/product_extension_interface_hacktory.php';
}

use Magento\Framework\Serialize\SerializerInterface;
use Dotdigitalgroup\Email\Model\ResourceModel\Rules as RulesResource;
use Dotdigitalgroup\Email\Model\Rules;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Quote\Model\ResourceModel\Quote\Collection as QuoteCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;

/**
 * @magentoDataFixture Magento/Customer/_files/customer.php
 * @magentoDataFixture Magento/Customer/_files/customer_address.php
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class RulesTest extends \PHPUnit\Framework\TestCase
{
    const RULE_OPERATOR_AND = 1;
    const RULE_OPERATOR_OR = 2;

    /**
     * @var QuoteCollection
     */
    private $quoteCollection;

    /**
     * @var int
     */
    private $currentWebsiteId;

    public function setUp()
    {
        include __DIR__ . '/../_files/products.php';
        $this->quoteCollection = ObjectManager::getInstance()->create(QuoteCollection::class);

        /** @var StoreManagerInterface $storeManager */
        $storeManager = ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->currentWebsiteId = $storeManager->getStore()->getWebsiteId();
    }

    /**
     * @param string $type
     * @return Quote\Address
     */
    private function createQuoteAddress($type)
    {
        /** @var AddressRepositoryInterface $addressRepository */
        $addressRepository = ObjectManager::getInstance()->create(AddressRepositoryInterface::class);

        /** @var Quote\Address $quoteAddress */
        $quoteAddress = ObjectManager::getInstance()->create(Quote\Address::class);
        $quoteAddress->importCustomerAddressData($addressRepository->getById(1));
        $quoteAddress->setData('address_type', $type);
        return $quoteAddress;
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    private function getCustomer()
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = ObjectManager::getInstance()->create(CustomerRepositoryInterface::class);

        return $customerRepository->getById(1);
    }

    /**
     * @return Product
     */
    private function getProduct()
    {
        /** @var ProductResource $resourceModel */
        $resourceModel = ObjectManager::getInstance()->create(ProductResource::class);
        /** @var Product $product */
        $product = ObjectManager::getInstance()->create(Product::class);
        $resourceModel->load($product, 1);

        return $product;
    }

    /**
     * @return Quote
     */
    private function createQuote()
    {
        /** @var Quote $quote */
        $quote = ObjectManager::getInstance()->create(Quote::class);
        $quote->setStoreId(1);
        $quote->setIsActive(true);
        $quote->setData('is_multi_shipping', false);
        $quote->assignCustomerWithAddressChange($this->getCustomer());
        $quote->setShippingAddress($this->createQuoteAddress('shipping'));
        $quote->setBillingAddress($this->createQuoteAddress('billing'));
        $quote->setCheckoutMethod('customer');
        $quote->setReservedOrderId('test_order_1');
        $quote->addProduct($this->getProduct(), 2);

        return $quote;
    }

    private function createQuoteWithCityAddress($city)
    {
        /** @var Quote $quote */
        $quote = ObjectManager::getInstance()->create(Quote::class);
        $quote->setStoreId(1);
        $quote->setIsActive(true);
        $quote->setData('is_multi_shipping', false);
        $quote->assignCustomerWithAddressChange($this->getCustomer());

        $shippingAddress = $this->createQuoteAddress('shipping');
        $shippingAddress->setCity($city);
        $billingAddress = $this->createQuoteAddress('billing');
        $billingAddress->setCity($city);
        $quote->setShippingAddress($shippingAddress);
        $quote->setBillingAddress($billingAddress);

        $quote->setCheckoutMethod('customer');
        $quote->setReservedOrderId('test_order_no_address_1');
        $quote->addProduct($this->getProduct(), 2);

        $quoteResource = ObjectManager::getInstance()->create(QuoteResource::class);
        $quoteResource->save($quote);

        return $quote;
    }

    /**
     * @return Quote
     */
    public function createQuoteWithoutPayment()
    {
        $quote = $this->createQuote();
        /** @var QuoteResource $quoteResource */
        $quoteResource = ObjectManager::getInstance()->create(QuoteResource::class);
        $quoteResource->save($quote);
        return $quote;
    }

    /**
     * @param int $operator
     *
     * @return Rules
     */
    private function createAbandonedCartRuleWithOperator($operator)
    {
        if (! in_array($operator, [self::RULE_OPERATOR_AND, self::RULE_OPERATOR_OR])) {
            throw new \InvalidArgumentException('Invalid rule operator, must be 1 (AND) or 2 (OR)');
        }

        /** @var Rules $rule */
        $rule = ObjectManager::getInstance()->create(Rules::class);

        $rule->setData('status', 1);
        $rule->setData('type', Rules::ABANDONED);
        $rule->setData('combination', $operator);

        $rule->setData('website_ids', [$this->currentWebsiteId]);
        return $rule;
    }

    /**
     * @param string $attribute
     * @param string $condition
     * @param string $value
     * @param int $operator
     *
     * @return Rules
     */
    private function createAbandonedCartRuleWithCondition($attribute, $condition, $value, $operator)
    {
        $rule = $this->createAbandonedCartRuleWithOperator($operator);

        $this->addConditionToRule($rule, $attribute, $condition, $value);

        return $rule;
    }

    /**
     * @param Rules $rule
     * @param string $attribute
     * @param string $condition
     * @param string $value
     *
     * @return null
     */
    private function addConditionToRule(Rules $rule, $attribute, $condition, $value)
    {
        $conditions = $this->getConditionsFromRule($rule);
        $conditions[] = [
            'attribute'  => $attribute,
            'conditions' => $condition,
            'cvalue'     => $value,
        ];

        $rule->setData('website_ids', $this->getWebsiteIdsFromRule($rule));
        $rule->setData('conditions', $conditions);

        /** @var RulesResource $rulesResource */
        $rulesResource = ObjectManager::getInstance()->get(RulesResource::class);
        $rulesResource->save($rule);
    }

    /**
     * @param Rules $rule
     * @return array
     */
    private function getWebsiteIdsFromRule(Rules $rule)
    {
        $websiteIds = $rule->getData('website_ids');
        return is_array($websiteIds) ?
            $websiteIds :
            explode(',', $websiteIds);
    }

    /**
     * @param Rules $rule
     *
     * @return array
     */
    private function getConditionsFromRule(Rules $rule)
    {
        $serializer = ObjectManager::getInstance()->create(SerializerInterface::class);
        $conditions = $rule->getData('conditions') ? $rule->getData('conditions') : [];
        if (is_string($conditions)) {
            $conditions = $serializer->unserialize($conditions);
        }

        return $conditions;
    }

    /**
     * @param Quote $expected
     * @return void
     */
    private function assertQuoteCollectionNotContains(Quote $expected)
    {
        $message = sprintf(
            'The quote with ID "%s" is contained in the quote collection, but was expected to be absent',
            $expected->getId()
        );
        $this->assertNotContains($expected->getId(), $this->quoteCollection->getAllIds(), $message);
    }

    /**
     * @param Quote $expected
     */
    private function assertQuoteCollectionContains(Quote $expected)
    {
        $message = sprintf(
            'The quote with ID "%s" is not contained in the quote collection, but is absent',
            $expected->getId()
        );
        $this->assertContains($expected->getId(), $this->quoteCollection->getAllIds(), $message);
    }

    /**
     * @param float $subtotal
     *
     * @return Quote
     */
    private function createQuoteWithSubtotal($subtotal)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->createQuote();
        $quote->setSubtotal($subtotal);
        $quoteResource = ObjectManager::getInstance()->create(QuoteResource::class);
        $quoteResource->save($quote);

        return $quote;
    }

    /**
     * @return void
     */
    public function testRuleWithSubtotalCondition()
    {
        //subtotal
        $conditionValue = '300.00';
        $attribute = 'subtotal';
        $this->createAbandonedCartRuleWithCondition($attribute, 'gteq', $conditionValue, self::RULE_OPERATOR_AND);

        $quote = $this->createQuoteWithSubtotal('500.00');
        $quote1 = $this->createQuoteWithSubtotal('1000.11');
        $quote2 = $this->createQuoteWithSubtotal('999.11');
        $quote3 = $this->createQuoteWithSubtotal('200.00');

        /** @var Rules $ruleService */
        $ruleService = ObjectManager::getInstance()->create(Rules::class);
        $ruleService->process($this->quoteCollection, Rules::ABANDONED, $this->currentWebsiteId);

        $this->assertQuoteCollectionNotContains($quote);
        $this->assertQuoteCollectionNotContains($quote1);
        $this->assertQuoteCollectionNotContains($quote2);
        $this->assertQuoteCollectionContains($quote3);
    }

    /**
     * @return void
     */
    public function testMultipleRules()
    {
        $rule = $this->createAbandonedCartRuleWithOperator(self::RULE_OPERATOR_AND);
        $rule->setData('conditions', [[
            'attribute' => 'customer_group_id',
            'conditions' => 'neq',
            'cvalue' => '1',
        ], [
            'attribute' => 'subtotal',
            'conditions' => 'lteq',
            'cvalue' => '200.00',
        ]]);

        /** @var RulesResource $rulesResource */
        $rulesResource = ObjectManager::getInstance()->get(RulesResource::class);
        $rulesResource->save($rule);

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote1 = $this->createQuote();
        $quote1->setSubtotal(200.00);
        $quote1->setCustomerGroupId('1');

        $quote2 = $this->createQuote();
        $quote2->setSubtotal(100.00);
        $quote2->setCustomerGroupId('1');

        $quote3 = $this->createQuote();
        $quote3->setSubtotal(2401.00);
        $quote3->setCustomerGroupId('2');

        $quote4 = $this->createQuote();
        $quote4->setSubtotal(201.00);
        $quote4->setCustomerGroupId('1');

        $quoteResource = ObjectManager::getInstance()->create(QuoteResource::class);
        $quoteResource->save($quote1);
        $quoteResource->save($quote2);
        $quoteResource->save($quote3);
        $quoteResource->save($quote4);

        /** @var Rules $ruleService */
        $ruleService = ObjectManager::getInstance()->create(Rules::class);
        $ruleService->process($this->quoteCollection, Rules::ABANDONED, $this->currentWebsiteId);

        $this->assertQuoteCollectionContains($quote1);
        $this->assertQuoteCollectionContains($quote4);
        $this->assertQuoteCollectionNotContains($quote2);
        $this->assertQuoteCollectionNotContains($quote3);
    }

    /**
     * Test shipping city AC is excluded by the exclusion rules.
     */
    public function testShippingCityNullValue()
    {
        //create a rule to exclude the city attribute from the abandoned carts
        $attribute = 'city';
        $value = $cityOne = 'CityM';
        $city = 'Croydon - Home of Champions';
        $this->createAbandonedCartRuleWithCondition($attribute, 'eq', $value, self::RULE_OPERATOR_AND);

        $quote = $this->createQuoteWithCityAddress($city);
        $quote1 = $this->createQuoteWithCityAddress($cityOne);

        /** @var Rules $ruleService */
        $ruleService = ObjectManager::getInstance()->create(Rules::class);
        $ruleService->process($this->quoteCollection, Rules::ABANDONED, $this->currentWebsiteId);

        $this->assertQuoteCollectionContains($quote);
        $this->assertQuoteCollectionNotContains($quote1);
    }
}
