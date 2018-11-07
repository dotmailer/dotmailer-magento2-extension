<?php

namespace Dotdigitalgroup\Email\Test\Integration\AbandonedCarts;

class Fixture
{
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param int $isActive
     * @param int $reservedOrderId
     * @param boolean $forCustomer
     */
    public function createQuote($objectManager, $isActive, $reservedOrderId, $forCustomer = false)
    {
        \Magento\TestFramework\Helper\Bootstrap::getInstance()->loadArea('frontend');
        $product = $objectManager->create(
            \Magento\Catalog\Model\Product::class
        );
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
            ->setStockData(
                [
                    'qty' => 100,
                    'is_in_stock' => 1,
                ]
            )->save();

        $productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
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

        $billingAddress = $objectManager->create(
            \Magento\Quote\Model\Quote\Address::class,
            ['data' => $addressData]
        );
        $billingAddress->setAddressType('billing');

        $shippingAddress = clone $billingAddress;
        $shippingAddress->setId(null)->setAddressType('shipping');

        $store = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)
            ->getStore();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $objectManager->create(
            \Magento\Quote\Model\Quote::class
        );

        if ($forCustomer) {
            $customerRepository = $objectManager->create(
                \Magento\Customer\Api\CustomerRepositoryInterface::class
            );
            $customerId = 1;
            $customer = $customerRepository->getById($customerId);

            $quote->setCustomerIsGuest(false)
                ->setCustomer($customer);
        } else {
            $quote->setCustomerIsGuest(true);
        }

        $quote->setIsActive($isActive)
            ->setStoreId($store->getId())
            ->setReservedOrderId($reservedOrderId)
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->addProduct($product);

        $quote->getPayment()->setMethod('checkmo');
        $quote->setIsMultiShipping('1');
        $quote->collectTotals();
        $quote->save();

        /** @var \Magento\Quote\Model\QuoteIdMask $quoteIdMask */
        $quoteIdMask = $objectManager->create(\Magento\Quote\Model\QuoteIdMaskFactory::class)
            ->create();
        $quoteIdMask->setQuoteId($quote->getId());
        $quoteIdMask->setDataChanges(true);
        $quoteIdMask->save();
    }
}
