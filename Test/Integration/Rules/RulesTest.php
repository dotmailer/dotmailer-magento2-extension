<?php

namespace Dotdigitalgroup\Email\Model\Rules;

use Dotdigitalgroup\Email\Model\ResourceModel\Rules as RulesResource;
use Dotdigitalgroup\Email\Model\Rules;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Quote\Model\ResourceModel\Quote\Collection as QuoteCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\ObjectManager;

/**
 * @magentoDataFixture Magento/Customer/_files/customer.php
 * @magentoDataFixture Magento/Customer/_files/customer_address.php
 * @magentoDataFixture Magento/Catalog/_files/products.php
 */
class RulesTest extends \PHPUnit_Framework_TestCase
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

    private function getCustomer()
    {
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = ObjectManager::getInstance()->create(CustomerRepositoryInterface::class);
        return $customerRepository->getById(1);
    }

    private function getProduct()
    {
        /** @var ProductResource $resourceModel */
        $resourceModel = ObjectManager::getInstance()->create(ProductResource::class);
        /** @var Product $product */
        $product = ObjectManager::getInstance()->create(Product::class);
        $resourceModel->load($product, 1);
        return $product;
    }

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

    private function createQuoteWithPayment($paymentCode)
    {
        $quote = $this->createQuote();
        $quote->getPayment()->setMethod($paymentCode);

        /** @var QuoteResource $quoteResource */
        $quoteResource = ObjectManager::getInstance()->create(QuoteResource::class);
        $quoteResource->save($quote);

        return $quote;
    }

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
     */
    private function addConditionToRule(Rules $rule, $attribute, $condition, $value)
    {
        $conditions = $this->getConditionsFromRule($rule);
        $conditions[] = [
            'attribute'  => $attribute,
            'conditions' => $condition,
            'cvalue'     => $value,
        ];
        $rule->setData('condition', $conditions);
        $rule->setData('website_ids', $this->getWebsiteIdsFromRule($rule));

        /** @var RulesResource $rulesResource */
        $rulesResource = ObjectManager::getInstance()->get(RulesResource::class);
        $rulesResource->save($rule);
    }

    private function getWebsiteIdsFromRule(Rules $rule)
    {
        $websiteIds = $rule->getData('website_ids');
        return is_array($websiteIds) ?
            $websiteIds :
            explode(',', $websiteIds);
    }

    /**
     * @param Rules $rule
     * @return array
     */
    private function getConditionsFromRule(Rules $rule)
    {
        $conditions = $rule->getData('condition') ? $rule->getData('condition') : [];
        if (is_string($conditions)) {
            $conditions = unserialize($conditions);
        }
        return $conditions;
    }

    protected function setUp()
    {
        $this->quoteCollection = ObjectManager::getInstance()->create(QuoteCollection::class);

        /** @var StoreManagerInterface $storeManager */
        $storeManager = ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->currentWebsiteId = $storeManager->getStore()->getWebsiteId();
    }

    private function assertQuoteCollectionContains(Quote $expected)
    {
        $message = sprintf('The quote with ID "%s" is not contained in the quote collection', $expected->getId());
        $this->assertContains($expected->getId(), $this->quoteCollection->getAllIds(), $message);
    }

    private function assertQuoteCollectionNotContains(Quote $expected)
    {
        $message = sprintf(
            'The quote with ID "%s" is contained in the quote collection, but was expected to be absent',
            $expected->getId()
        );
        $this->assertNotContains($expected->getId(), $this->quoteCollection->getAllIds(), $message);
    }

    public function testExcludeByPaymentMethodOnORCondition()
    {
        $this->markTestSkipped();
        $quoteToBeExcluded = $this->createQuoteWithPayment('paypal');
        $quoteToBeIncluded1 = $this->createQuoteWithPayment('foo');
        $quoteToBeIncluded2 = $this->createQuoteWithoutPayment();
        $this->createAbandonedCartRuleWithCondition('method', 'eq', 'paypal', self::RULE_OPERATOR_OR);

        /** @var Rules $ruleService */
        $ruleService = ObjectManager::getInstance()->create(Rules::class);
        $ruleService->process($this->quoteCollection, Rules::ABANDONED, $this->currentWebsiteId);

        $this->assertQuoteCollectionNotContains($quoteToBeExcluded);
        $this->assertQuoteCollectionContains($quoteToBeIncluded1);
        $this->assertQuoteCollectionContains($quoteToBeIncluded2);
    }

    public function testExcludeByPaymentMethodOnANDCondition()
    {
        $this->markTestSkipped();
        $quoteToBeExcluded = $this->createQuoteWithPayment('paypal');
        $quoteToBeIncluded1 = $this->createQuoteWithPayment('foo');
        $quoteToBeIncluded2 = $this->createQuoteWithoutPayment();
        $this->createAbandonedCartRuleWithCondition('method', 'eq', 'paypal', self::RULE_OPERATOR_AND);

        /** @var Rules $ruleService */
        $ruleService = ObjectManager::getInstance()->create(Rules::class);
        $ruleService->process($this->quoteCollection, Rules::ABANDONED, $this->currentWebsiteId);

        $this->assertQuoteCollectionNotContains($quoteToBeExcluded);
        $this->assertQuoteCollectionContains($quoteToBeIncluded1);
        $this->assertQuoteCollectionContains($quoteToBeIncluded2);
    }

    public function testExcludeByTwoPaymentMethodsOnANDCondition()
    {
        $this->markTestSkipped();
        $quoteToBeExcluded1 = $this->createQuoteWithPayment('paypal');
        $quoteToBeExcluded2 = $this->createQuoteWithPayment('checkmo');
        $quoteToBeIncluded1 = $this->createQuoteWithPayment('foo');
        $quoteToBeIncluded2 = $this->createQuoteWithoutPayment();
        $rule = $this->createAbandonedCartRuleWithCondition('method', 'eq', 'paypal', self::RULE_OPERATOR_AND);
        $this->addConditionToRule($rule, 'method', 'eq', 'checkmo');

        /** @var Rules $ruleService */
        $ruleService = ObjectManager::getInstance()->create(Rules::class);
        $ruleService->process($this->quoteCollection, Rules::ABANDONED, $this->currentWebsiteId);

        $this->assertQuoteCollectionNotContains($quoteToBeExcluded1);
        $this->assertQuoteCollectionNotContains($quoteToBeExcluded2);
        $this->assertQuoteCollectionContains($quoteToBeIncluded1);
        $this->assertQuoteCollectionContains($quoteToBeIncluded2);
    }

    private function createQuoteWithSubtotal($subtotal)
    {
        $quote = $this->createQuote();

        $quote->setSubtotal($subtotal);
        $quoteResource = ObjectManager::getInstance()->create(QuoteResource::class);
        $quoteResource->save($quote);

        return $quote;
    }

    public function testRuleWithSubtotalCondition()
    {
        //subtotal
        $conditionValue = '300.00';
        $attribute = 'subtotal';
        $this->createAbandonedCartRuleWithCondition($attribute, 'gteq', $conditionValue, self::RULE_OPERATOR_AND);

        $quote = $this->createQuoteWithSubtotal('500.00');
        $quote1 = $this->createQuoteWithSubtotal('1000.11');
        $quote2 = $this->createQuoteWithSubtotal('999.11');

        /** @var Rules $ruleService */
        $ruleService = ObjectManager::getInstance()->create(Rules::class);
        $ruleService->process($this->quoteCollection, Rules::ABANDONED, $this->currentWebsiteId);


        $this->assertQuoteCollectionNotContains($quote);
        $this->assertQuoteCollectionNotContains($quote1);
        $this->assertQuoteCollectionNotContains($quote2);
    }

    public function testRuleWithCustomerSegmentANDPaymentMethod()
    {
        $attribute1  = 'method';
        $attribute2 = 'customer_group_id';
        $value1 = '1';
        $value2 = 'payflow_advanced';

        $this->createAbandonedCartRuleWithCondition($attribute1, 'neq', $value1, self::RULE_OPERATOR_AND);
        $this->createAbandonedCartRuleWithCondition($attribute2, 'eq', $value2, self::RULE_OPERATOR_AND);
    }
}
