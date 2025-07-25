<?php

namespace Dotdigitalgroup\Email\Test\Integration\Controller\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\Customer\Account\Configuration;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\Collection as ContactCollection;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;

class NewsletterTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @var Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockHelper;

    /**
     * @var Configuration|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockAccountConfig;

    /**
     * @var Client|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockClient;

    /**
     * @var Contact|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockContact;

    /**
     * @var ContactCollection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockContactCollection;

    /**
     * @var ContactCollectionFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockContactCollectionFactory;

    /**
     * @var Customer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockCustomer;

    /**
     * @var \StdClass
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

    public function setUp(): void
    {
        parent::setUp();

        $objectManager = ObjectManager::getInstance();

        $this->mockFormKeyValidator = $this->createMock(FormKeyValidator::class);
        $this->mockContact = $this->getMockBuilder(Contact::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByCustomerEmail'])
            ->addMethods(['getContactId'])
            ->getMock();
        $this->mockContactCollection = $this->createMock(ContactCollection::class);
        $this->mockContactCollectionFactory = $this->createMock(ContactCollectionFactory::class);
        $this->mockCustomer = $this->createMock(Customer::class);
        $this->mockHelper = $this->createMock(Data::class);
        $this->mockAccountConfig = $this->createMock(Configuration::class);
        $this->mockClient = $this->createMock(Client::class);
        $this->mockStore = $this->createMock(Store::class);
        $this->mockStoreManager = $this->createMock(StoreManagerInterface::class);
        $this->mockCustomerSession = $this->createMock(CustomerSession::class);
        $this->mockECContactObject = (object) ['id' => '111'];

        $objectManager->addSharedInstance($this->mockFormKeyValidator, FormKeyValidator::class);
        $objectManager->addSharedInstance($this->mockCustomerSession, CustomerSession::class);
        $objectManager->addSharedInstance($this->mockHelper, Data::class);
        $objectManager->addSharedInstance($this->mockContactCollectionFactory, ContactCollectionFactory::class);
        $objectManager->addSharedInstance($this->mockAccountConfig, Configuration::class);
    }

    public function testAdditionalSubscriptionsAreProcessed()
    {
        $this->setUpSharedMocks();

        $this->mockAccountConfig->expects($this->once())
            ->method('getAddressBookIdsToShow')
            ->willReturn([0,1,2]);
        $this->mockAccountConfig->expects($this->once())
            ->method('canShowAddressBooks')
            ->willReturn(true);

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('/connector/customer/newsletter?additional_subscriptions[]=1&additional_subscriptions[]=2');
    }

    public function testContactDataFieldsAreUpdatedByEmail()
    {
        $this->setUpSharedMocks();

        $this->mockAccountConfig->method('getAddressBookIdsToShow')
            ->willReturn([]);
        $this->mockAccountConfig->method('canShowAddressBooks')
            ->willReturn(false);

        $this->getRequest()->setParam('data_fields', ['key' => 'dummy']);
        $this->mockAccountConfig->method('canShowDataFields')->willReturn(true);
        $this->mockClient->method('getDataFields')->willReturn([]);
        $this->mockClient->expects($this->atLeastOnce())
            ->method('updateContactDatafieldsByEmail')
            ->willReturn(null);

        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->dispatch('/connector/customer/newsletter');
    }

    private function setUpSharedMocks()
    {
        $contactId = 12;
        $websiteId = 1;

        $this->mockFormKeyValidator->method('validate')
            ->willReturn(true);

        $this->mockCustomerSession->method('isLoggedIn')
            ->willReturn(true);

        $this->mockStoreManager->method('getStore')
            ->willReturn($this->mockStore);

        $this->mockStore->method('getWebsiteId')
            ->willReturn($websiteId);

        $this->mockHelper->method('isEnabled')
            ->with($websiteId)
            ->willReturn(true);

        $this->mockCustomerSession->method('getCustomer')
            ->willReturn($this->mockCustomer);

        $this->mockCustomer->method('__call')->with('getEmail')
            ->willReturn('test@emailsim.io');

        $this->mockContactCollectionFactory->method('create')
            ->willReturn($this->mockContactCollection);

        $this->mockContactCollection->method('loadByCustomerEmail')
            ->willReturn($this->mockContact);

        $this->mockContact->method('getContactId')
            ->willReturn(20);

        $this->mockHelper->method('getWebsiteApiClient')
            ->willReturn($this->mockClient);

        $this->mockClient->method('getContactById')
            ->willReturn($this->mockECContactObject);
    }
}
