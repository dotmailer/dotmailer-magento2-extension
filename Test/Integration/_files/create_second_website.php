<?php
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$storeFactory = $objectManager->get('Magento\Store\Model\StoreFactory')->create();

$groupFactory = $objectManager->get('Magento\Store\Model\GroupFactory')->create();

/** @var $websiteResource \Magento\Store\Model\ResourceModel\Website */
$websiteResource = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Store\Model\ResourceModel\Website::class
);
$website = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Store\Model\Website::class
);

$website->setName('testStore');
$website->setCode('test');
$websiteResource->save($website);

/** @var $groupResourceModel \Magento\Store\Model\ResourceModel\Group */
$groupResourceModel = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Store\Model\ResourceModel\Group::class
);

$groupFactory->setWebsiteId($website->getId());
$groupFactory->setName('My Custom Group Name');
$groupFactory->setRootCategoryId(2);
$groupFactory->setDefaultStoreId(3);
$groupResourceModel->save($groupFactory);

/** @var $storeResourceModel \Magento\Store\Model\ResourceModel\Store; */
$storeResourceModel = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Store\Model\ResourceModel\Store::class
);

$storeFactory->load('my_custom_store_code');
$groupFactory->load('My Custom Group Name', 'name');
$storeFactory->setCode('my_custom_store_code');
$storeFactory->setName('Mu Custom Store Code');
$storeFactory->setWebsite($website);
$storeFactory->setGroupId($groupFactory->getId());
$storeFactory->setData('is_active', '1');
$storeResourceModel->save($storeFactory);

/**@var $eventManager \Magento\Framework\Event\ManagerInterface; */
$eventManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Framework\Event\ManagerInterface::class
);

$eventManager->dispatch('store_add', ['store' => $storeFactory]);
