<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\Data\ProductInterface;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
/** @var Magento\Store\Model\Website $website */
$secondWebsite = $objectManager->get(\Magento\Store\Api\WebsiteRepositoryInterface::class)->get('test');

$objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)->reinitStores();

/** @var $product \Magento\Catalog\Model\Product */
$product = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(ProductInterface::class);
$product
    ->setId(2)
    ->setTypeId('simple')
    ->setAttributeSetId(4)
    ->setWebsiteIds([$secondWebsite->getId()])
    ->setName('Simple Product 2')
    ->setSku('ddg-fixture-product')
    ->setPrice(10)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 0])
    ->save();
