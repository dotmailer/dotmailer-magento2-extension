<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Contact;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\TestFramework\ObjectManager;

class NewsletterTest extends \Magento\TestFramework\TestCase\AbstractController
{
    public function testAdditionalSubscriptions()
    {
        /** @var ObjectManager $objectManager */
        $objectManager = ObjectManager::getInstance();
        $contactId = 12;

        $mockFormKeyValidator = $this->getMockBuilder(FormKeyValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFormKeyValidator->method('validate')->willReturn(true);
        $objectManager->addSharedInstance($mockFormKeyValidator, FormKeyValidator::class);

        $sessionMethods = array_merge(get_class_methods(CustomerSession::class), ['getConnectorContactId']);
        $mockCustomerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods($sessionMethods)
            ->getMock();
        $mockCustomerSession->method('getConnectorContactId')
            ->willReturn(1);

        $mockContact = $this->getMockBuilder(Contact::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContactId'])
            ->getMock();
        $mockContact->method('getContactId')
            ->willReturn($contactId);
        $mockContact->id = $contactId;

        $mockWebsite = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockStore = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockStore->method('getWebsite')->willReturn($mockWebsite);

        $mockCustomer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockCustomer->method('getStore')->willReturn($mockStore);

        $mockCustomerSession->method('getCustomer')->willReturn($mockCustomer);

        $objectManager->addSharedInstance($mockCustomerSession, CustomerSession::class);

        $mockHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(get_class_methods(Data::class))
            ->getMock();

        $mockHelper->expects($this->once())
            ->method('isEnabled')
            ->with($mockWebsite)
            ->willReturn(true);

        $mockClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(get_class_methods(Client::class))
            ->getMock();
        $mockClient->method('getContactById')
            ->willReturn($mockContact);

        $mockClient->expects($this->at(1))
            ->method('postAddressBookContacts')
            ->with(1, $mockContact);
        $mockClient->expects($this->at(2))
            ->method('postAddressBookContacts')
            ->with(2, $mockContact);

        $mockHelper->method('getWebsiteApiClient')
            ->with($mockWebsite)
            ->willReturn($mockClient);

        $mockHelper->method('getAddressBookIdsToShow')
            ->willReturn([0,1,2]);
        $mockHelper->method('getCanShowAdditionalSubscriptions')
            ->willReturn(true);
        $mockHelper->method('getContactByEmail')
            ->willReturn($mockContact);

        $objectManager->addSharedInstance($mockHelper, Data::class);

        $this->dispatch('connector/customer/newsletter?additional_subscriptions[]=1&additional_subscriptions[]=2');
    }

    public function testContactDataFieldsAreUpdatedByEmail()
    {
        ///updatecontactdatafieldsbyemail called
        /** @var ObjectManager  $objectManager */
        $objectManager = ObjectManager::getInstance();

        $mockFormKeyValidator = $this->getMockBuilder(FormKeyValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFormKeyValidator->method('validate')->willReturn(true);
        $objectManager->addSharedInstance($mockFormKeyValidator, FormKeyValidator::class);

        $sessionMethods = array_merge(get_class_methods(CustomerSession::class), [
            'getConnectorContactId',
        ]);
        /**
         * Customer Session mock.
         */
        $mockCustomerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods($sessionMethods)
            ->getMock();
        $mockCustomerSession->method('getConnectorContactId')->willReturn('dummy123');
        $mockCustomerSession->method('getCustomerId')->willReturn(1);
        $objectManager->addSharedInstance($mockCustomerSession, CustomerSession::class);

        /**
         * Customer mock.
         */
        $mockCustomer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * Mock website
         */
        $mockWebsite = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * Store mock.
         */
        $mockStore = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * Helper mock.
         */
        $mockHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * Client mock.
         */
        $mockClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * Store mockager
         */
        $mockStoreManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();

        /**
         * Mocktact
         */
        $mockContact = $this->getMockBuilder(Contact::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContactId'])
            ->getMock();

        //\Dotdigitalgroup\Email\Helper\Data
        $objectManager->addSharedInstance($mockHelper, Data::class);

        $mockHelper->storeManager = $mockStoreManager;
        $mockWebsite->method('getId')->willReturn(0);
        $mockStore->method('getWebsite')->willReturn($mockWebsite);
        $mockStoreManager->method('getStore')->willReturn($mockStore);
        $mockCustomerSession->method('getCustomer')->willReturn($mockCustomer);
        $mockCustomer->method('getStore')->willReturn($mockStore);
        $mockContact->method('getContactId')->willReturn(1);
        $mockStore->method('getWebsite')->willReturn('0');
        $mockHelper->method('isEnabled')->willReturn('1');
        $mockHelper->method('getWebsiteApiClient')->willReturn($mockClient);
        $mockHelper->method('getCanShowDataFields')->willReturn(true);
        $mockHelper->method('getContactByEmail')->willReturn($mockContact);
        $mockClient->method('getContactById')->willReturn((object) ['id' => '111']);
        $mockClient->method('setApiUsername')->willReturn($mockClient);
        $mockClient->method('getDataFields')->willReturn([]);
        $mockClient->method('setApipassword')->willReturn($mockClient);
        $mockClient->method('getContactById')->willReturn((object) ['id' => '111']);
        $mockClient->expects($this->atLeastOnce())->method('updateContactDatafieldsByEmail')->willReturn([]);

        $this->getRequest()->setParam('data_fields', ['key' => 'dummy']);

        $this->dispatch('connector/customer/newsletter');
    }
}
