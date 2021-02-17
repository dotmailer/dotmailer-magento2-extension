<?php

namespace Dotdigitalgroup\Email\Controller\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\TestFramework\ObjectManager;

class NewsletterTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockHelper;

    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockClient;

    /**
     * @var Contact\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockContact;

    /**
     * @var ContactFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockContactFactory;

    /**
     * @var Customer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockCustomer;

    /**
     * @var object \stdClass
     */
    private $mockECContactObject;

    /**
     * @var CustomerSession|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockCustomerSession;

    /**
     * @var FormKeyValidator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockFormKeyValidator;

    /**
     * @var Store|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockStore;

    /**
     * @var StoreManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockStoreManager;

    /**
     * @var Website|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockWebsite;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->mockFormKeyValidator = $this->createMock(FormKeyValidator::class);
        $this->mockContact = $this->getMockBuilder(Contact::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByCustomerEmail'])
            ->addMethods(['getContactId'])
            ->getMock();
        $this->mockContactFactory = $this->createMock(ContactFactory::class);
        $this->mockCustomer = $this->createMock(Customer::class);
        $this->mockHelper = $this->createMock(Data::class);
        $this->mockClient = $this->createMock(Client::class);
        $this->mockWebsite = $this->createMock(Website::class);
        $this->mockStore = $this->createMock(Store::class);
        $this->mockStoreManager = $this->createMock(StoreManagerInterface::class);

        $sessionMethods = array_merge(get_class_methods(CustomerSession::class), ['getConnectorContactId']);
        $this->mockCustomerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods($sessionMethods)
            ->getMock();

        $this->mockECContactObject = (object) ['id' => '111'];

        $this->objectManager->addSharedInstance($this->mockFormKeyValidator, FormKeyValidator::class);
        $this->objectManager->addSharedInstance($this->mockCustomerSession, CustomerSession::class);
        $this->objectManager->addSharedInstance($this->mockHelper, Data::class);
        $this->objectManager->addSharedInstance($this->mockContactFactory, ContactFactory::class);
    }

    public function testAdditionalSubscriptionsAreProcessed()
    {
        $this->setUpSharedMocks();

        $this->mockHelper->method('getAddressBookIdsToShow')
            ->willReturn([0,1,2]);
        $this->mockHelper->method('getCanShowAdditionalSubscriptions')
            ->willReturn(true);

        $this->mockClient->expects($this->at(1))
            ->method('postAddressBookContacts')
            ->with(1, $this->mockECContactObject);
        $this->mockClient->expects($this->at(2))
            ->method('postAddressBookContacts')
            ->with(2, $this->mockECContactObject);

        $this->dispatch('connector/customer/newsletter?additional_subscriptions[]=1&additional_subscriptions[]=2');
    }

    public function testContactDataFieldsAreUpdatedByEmail()
    {
        $this->setUpSharedMocks();

        $this->mockHelper->method('getAddressBookIdsToShow')
            ->willReturn([]);
        $this->mockHelper->method('getCanShowAdditionalSubscriptions')
            ->willReturn(false);

        $this->getRequest()->setParam('data_fields', ['key' => 'dummy']);
        $this->mockHelper->method('getCanShowDataFields')->willReturn(true);
        $this->mockClient->method('getDataFields')->willReturn([]);
        $this->mockClient->expects($this->atLeastOnce())
            ->method('updateContactDatafieldsByEmail')
            ->willReturn(null);

        $this->dispatch('connector/customer/newsletter');
    }

    private function setUpSharedMocks()
    {
        $contactId = 12;
        $websiteId = 1;

        $this->mockFormKeyValidator->method('validate')
            ->willReturn(true);

        $this->mockCustomerSession->method('getConnectorContactId')
            ->willReturn(1);
        $this->mockCustomerSession->method('getCustomerId')
            ->willReturn(1);

        $this->mockStoreManager->method('getStore')
            ->willReturn($this->mockStore);

        $this->mockStore->method('getWebsite')
            ->willReturn($this->mockWebsite);

        $this->mockContact->method('getContactId')
            ->willReturn($contactId);

        $this->mockContact->id = $contactId;

        $this->mockCustomer->method('getStore')
            ->willReturn($this->mockStore);

        $this->mockCustomerSession->method('getCustomer')
            ->willReturn($this->mockCustomer);

        $this->mockHelper->expects($this->once())
            ->method('isEnabled')
            ->with($websiteId)
            ->willReturn(true);

        $this->mockClient->method('getContactById')
            ->willReturn($this->mockECContactObject);

        $this->mockHelper->method('getWebsiteApiClient')
            ->willReturn($this->mockClient);

        $this->mockContactFactory->method('create')
            ->willReturn($this->mockContact);

        $this->mockContact->method('loadByCustomerEmail')
            ->willReturn($this->mockContact);
    }
}
