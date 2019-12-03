<?php

// @codingStandardsIgnoreFile

require __DIR__ . '/customer.php';
require __DIR__ . '/../_files/products.php';

$wishlist = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Wishlist\Model\Wishlist::class
);
$wishlist->loadByCustomerId($customer->getId(), true);
$item = $wishlist->addNewItem($product, new \Magento\Framework\DataObject([]));
$wishlist->setSharingCode('fixture_unique_code')
    ->save();
