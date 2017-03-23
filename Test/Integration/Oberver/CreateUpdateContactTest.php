<?php

namespace Dotdigitalgroup\Email\Observer\Customer;

/**
 * Class CreateUpdateContactTest
 * @package Dotdigitalgroup\Email\Observer\Customer
 * @magentoDBIsolation enabled
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

    public function setup()
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->customerFactory = $this->objectManager->create('\Magento\Customer\Model\CustomerFactory');
        $this->contactFactory = $this->objectManager->create('\Dotdigitalgroup\Email\Model\ContactFactory');
    }

    public function tearDown()
    {
    }

    public function prepare($email, $fname, $lname, $pass)
    {
        /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->create('\Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore();
        $website = $store->getWebsite();

        /**
         * @var \Dotdigitalgroup\Email\Model\ContactFactory
         */
        $customerModel = $this->customerFactory->create();
        $customerModel->setStore($store);
        $customerModel->setWebsiteId($website->getId());
        $customerModel->setEmail($email);
        $customerModel->setFirstname($fname);
        $customerModel->setLastname($lname);
        $customerModel->setPassword($pass);
        $customerModel->save();

        return $customerModel;
    }

    /**
     * Test contact created successfully
     *
     * @dataProvider dataProvider
     *
     * @param $email
     * @param $fname
     * @param $lname
     * @param $pass
     */
    public function testContactCreatedAndUpdatedSuccessfully($email, $fname, $lname, $pass)
    {
        $customerModel = $this->prepare($email, $fname, $lname, $pass);
        $contact = $this->contactFactory->create()->loadByCustomerId($customerModel->getId());
        $this->assertEquals($email, $contact->getEmail(), 'Contact was not created');
        unset($contact);


        $email = str_replace('new', 'updated', $email);
        $customerModel->setEmail($email)->save();
        $contact = $this->contactFactory->create()->loadByCustomerId($customerModel->getId());
        $this->assertEquals($email, $contact->getEmail(), 'Contact was not updated');
    }

    /**
     *
     * @return array
     */
    public function dataProvider()
    {
        $num = rand(5, 15);
        return [
            [
                'dummy' . $num . 'new@dotmailer.com',
                'First Name',
                'Last Name',
                'pass123@'
            ]
        ];
    }
}