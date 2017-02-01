<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

require 'default_rollback.php';
include __DIR__ . '/email_order_guest.php';
include __DIR__ . '/../../../Magento/Customer/_files/two_customers.php';
require __DIR__ . '/../../../Magento/Catalog/_files/product_simple.php';
/** @var \Magento\Catalog\Model\Product $product */

$addressData = include __DIR__ . '/address_data.php';

$customerIdFromFixture = 1;

$order->setCustomerId($customerIdFromFixture)->save();

$payment2 = $objectManager->create('Magento\Sales\Model\Order\Payment');
$payment2->setMethod('checkmo');

/** @var \Magento\Sales\Model\Order $order */
$order = $objectManager->create('Magento\Sales\Model\Order');
$order->setIncrementId('100000002')
    ->setState(
        \Magento\Sales\Model\Order::STATE_PROCESSING
    )->setStatus(
        $order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
    )->setSubtotal(
        100
    )->setBaseSubtotal(
        100
    )->setBaseGrandTotal(
        100
    )->setCustomerIsGuest(
        true
    )->setCustomerEmail(
        'customer2@null.com'
    )->setBillingAddress(
        $billingAddress
    )->setShippingAddress(
        $shippingAddress
    )->setStoreId(
        $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId()
    )->addItem(
        $orderItem
    )->setPayment(
        $payment2
    );

$order->save();

$order->setCustomerId($customerIdFromFixture)->save();