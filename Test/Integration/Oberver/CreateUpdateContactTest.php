<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

/**
 * Class CreateUpdateContactTest
 * @package Dotdigitalgroup\Email\Observer\Customer
 * @magentoDBIsolation disabled
 */
class CreateUpdateContactTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    public $objectManager;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;
    /**
     * @var \Dotdigitalgroup\Email\Model\ContactFactory
     */
    public $contactFactory;
    public $email;
    public $customerModel;

    public $customerId ;


    public function setup()
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->customerFactory = $this->objectManager->create('\Magento\Customer\Model\CustomerFactory');
        $this->contactFactory = $this->objectManager->create('\Dotdigitalgroup\Email\Model\ContactFactory');

        $this->prepareCustomerData();

    }

    public function tearDown()
    {
        $this->contactFactory->create()
            ->loadByCustomerId($this->customerId)
            ->delete();
        $this->customerModel
            ->delete();

    }

    public function prepareCustomerData()
    {

        $helper = $this->getMock('Dotdigitalgroup\Email\Helper\Data', [], [], '', false);
        $helper->method('isEnabled')->willReturn(true);
        $this->objectManager->addSharedInstance($helper, \Dotdigitalgroup\Email\Helper\Data::class);

        /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->create('\Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore();
        $website = $store->getWebsite();
        $num = rand(500, 5000);
        $email = 'dummy' . $num . 'new@dotmailer.com';

        $customerModel = $this->customerFactory->create();
        $customerModel->setStore($store);
        $customerModel->setWebsiteId($website->getId());
        $customerModel->setEmail($email);
        $customerModel->setFirstname('Firstname');
        $customerModel->setLastname('Lastname');
        $customerModel->setPassword('dummypassword');
        $customerModel->save();
        $this->customerId = $customerModel->getId();

        $this->customerModel = $customerModel;
    }

    public function testContactCreatedSuccessfully()
    {
        //save new customer which will trigger the observer and will create new contact

        $contact = $this->contactFactory->create()
            ->loadByCustomerId($this->customerModel->getId());
        //check contact created after the customer was saved
        $this->assertEquals($this->customerModel->getEmail(), $contact->getEmail(), 'Contact was not created');
    }

    public function test_contact_updated_successfully()
    {
        //update the email and save contact
        $updatedEmail = str_replace('new', 'updated', $this->email);
        $customerModel = $this->customerModel
            ->setEmail($updatedEmail)
            ->save();

        $contact = $this->contactFactory->create()
            ->loadByCustomerId($customerModel->getId());

        $this->assertEquals($updatedEmail, $contact->getEmail(), 'Contact was not updated');
    }


}