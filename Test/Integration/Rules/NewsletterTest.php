<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Data\Form\FormKey\Validator as FromKeyValidator;
use Magento\Store\Model\Store;
use Magento\TestFramework\ObjectManager;

/**
 * Class NewsletterTest
 * @package Dotdigitalgroup\Email\Controller\Customer
 */
class NewsletterTest extends \Magento\TestFramework\TestCase\AbstractController
{
    public function testConnectorContactIdNotSetCausesRedirect()
    {
        /** @var ObjectManager $objectManager */
        $objectManager = ObjectManager::getInstance();

        $mockFormKeyValidator = $this->getMockBuilder(FromKeyValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFormKeyValidator->method('validate')->willReturn(true);
        $objectManager->addSharedInstance($mockFormKeyValidator, FromKeyValidator::class);

        $sessionMethods = array_merge(get_class_methods(CustomerSession::class), ['getConnectorContactId']);
        $mockCustomerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods($sessionMethods)
            ->getMock();

        $mockCustomerSession->method('getConnectorContactId')->willReturn(false);
        $objectManager->addSharedInstance($mockCustomerSession, CustomerSession::class);

        $mockCustomerSession->expects($this->never())->method('getCustomer');

        $this->dispatch('connector/customer/newsletter');

        $this->assertRedirect($this->stringContains('/customer/account/'));
    }

    public function testContactDataFieldsAreUpdatedByEmail()
    {
        $this->markTestSkipped();
        ///updatecontactdatafieldsbyemail called
        /** @var ObjectManager  $objectManager */
        $objectManager = ObjectManager::getInstance();

        $mockFormKeyValidator = $this->getMockBuilder(FromKeyValidator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockFormKeyValidator->method('validate')->willReturn(true);
        $objectManager->addSharedInstance($mockFormKeyValidator, FromKeyValidator::class);

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
        $customerMethods = array_merge(get_class_methods(Customer::class));
        $mockCustomer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();

        /**
         * Store mock.
         */
        $storeMethods = array_merge(get_class_methods(Store::class), ['getWebsite']);
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

        //\Dotdigitalgroup\Email\Helper\Data
        $objectManager->addSharedInstance($mockHelper, Data::class);



        $mockCustomerSession->method('getCustomer')->willReturn($mockCustomer);
        $mockCustomer->method('getStore')->willReturn($mockStore);
        $mockStore->method('getWebsite')->willReturn('0');
        $mockHelper->method('isEnabled')->willReturn('1');
        $mockHelper->method('getWebsiteApiClient')->willReturn($mockClient);
        $mockClient->method('getContactById')->willReturn((object) ['id' => '111']);
        $mockClient->method('setApiUsername')->willReturn('93465');
        $mockClient->method('getDataFields')->willReturn([]);
        $mockClient->method('setApipassword')->willReturn('pass');
        $mockClient->method('getContactById')->willReturn((object) ['id' => '111']);
        $mockClient->expects($this->atLeastOnce())->method('updateContactDatafieldsByEmail')->willReturn(true);


        $this->getRequest()->setParam('data_fields', []);

        $this->dispatch('connector/customer/newsletter');
    }
}
