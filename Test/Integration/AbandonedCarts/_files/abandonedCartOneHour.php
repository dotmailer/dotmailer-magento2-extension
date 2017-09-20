<?php

// @codingStandardsIgnoreFile
include __DIR__ . '/../../../Magento/Sales/_files/quote.php';
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$abandoned = $objectManager->create(\Dotdigitalgroup\Email\Model\Abandoned::class);
$fromTime = new \DateTime('now', new \DateTimezone('UTC'));
$fromTime->sub(\DateInterval::createFromDateString('1 hours'));
$fromTime->sub(\DateInterval::createFromDateString('1 minutes'));

$abandoned->setQuoteId(1)
    ->setStoreId('1')
    ->setCustomerId(1)
    ->setEmail('customer@dummy.com')
    ->setIsActive('1')
    ->setQuoteUpdatedAt($fromTime->format(\DateTime::ISO8601))
    ->setAbandonedCartNumber('1')
    ->setItemsCount(2)
    ->setItemsIds('2,3')
    ->setCreatedAt(time())
    ->setUpdatedAt(time())
    ->save();
