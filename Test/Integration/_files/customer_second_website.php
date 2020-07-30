<?php
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

\Magento\TestFramework\Helper\Bootstrap::getInstance()->loadArea(
    \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
);
Resolver::getInstance()->requireDataFixture('Dotdigitalgroup_Email::Test/Integration/_files/create_second_website.php');

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$customerResource = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Customer\Model\ResourceModel\Customer::class
);

$customerCollection = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Customer\Model\ResourceModel\Customer\Collection::class
);

$customerFactory = $objectManager->get('\Magento\Customer\Model\CustomerFactory')->create();

$secondWebsite = $objectManager->get(\Magento\Store\Api\WebsiteRepositoryInterface::class)->get('test');

$storeId = $secondWebsite->getStoreIds();
$storeId = reset($storeId);

/** @var Magento\Customer\Model\Customer $customer */
$customerFactory->setWebsiteId($secondWebsite->getId())
    ->setEntityId(2)
    ->setStoreId($storeId)
    ->setEmail('customer_sec_website@example.com')
    ->setPassword('password')
    ->setGroupId(1)
    ->setIsActive(1)
    ->setPrefix('Mr.')
    ->setFirstname('Chaz')
    ->setMiddlename('Sprat')
    ->setLastname('Kangaroo')
    ->setSuffix('Esq.')
    ->setDefaultBilling(1)
    ->setDefaultShipping(1)
    ->setTaxvat('12')
    ->setGender(0);

$customerResource->save($customerFactory);
