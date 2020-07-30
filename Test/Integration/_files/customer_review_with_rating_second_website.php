<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

\Magento\TestFramework\Helper\Bootstrap::getInstance()->loadArea(
    \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
);
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

\Magento\TestFramework\Helper\Bootstrap::getInstance()->loadArea(
    \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
);
Resolver::getInstance()->requireDataFixture(
    'Dotdigitalgroup_Email::Test/Integration/_files/customer_second_website.php'
);
Resolver::getInstance()->requireDataFixture(
    'Dotdigitalgroup_Email::Test/Integration/_files/product_simple.php'
);

$secondWebsite = $objectManager->get(\Magento\Store\Api\WebsiteRepositoryInterface::class)->get('test');

$storeId = $secondWebsite->getStoreIds();
$storeId = reset($storeId);

$review = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Review\Model\Review::class,
    ['data' => [
        'customer_id' => 2,
        'title' => 'Review Summary second website',
        'detail' => 'Review text second website',
        'nickname' => 'Nickname second website',
    ]]
);

$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$product = $productRepository->get('ddg-fixture-product');

$review
    ->setEntityId($review->getEntityIdByCode(\Magento\Review\Model\Review::ENTITY_PRODUCT_CODE))
    ->setEntityPkValue($product->getId())
    ->setStatusId(\Magento\Review\Model\Review::STATUS_APPROVED)
    ->setStoreId($storeId)
    ->setStores([$storeId])
    ->save();

/** @var \Magento\Review\Model\ResourceModel\Review\Collection $ratingCollection */
$ratingCollection = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Review\Model\Rating::class
)->getCollection()
    ->setPageSize(2)
    ->setCurPage(1);

foreach ($ratingCollection as $rating) {
    $rating->setStores([$storeId])->setIsActive(1)->save();
}

foreach ($ratingCollection as $rating) {
    $ratingOption = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
        ->create(\Magento\Review\Model\Rating\Option::class)
        ->getCollection()
        ->setPageSize(1)
        ->setCurPage(2)
        ->addRatingFilter($rating->getId())
        ->getFirstItem();
    $rating->setReviewId($review->getId())
        ->addOptionVote($ratingOption->getId(), $product->getId());
}
