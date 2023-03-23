<?php

namespace Dotdigitalgroup\Email\Test\Integration\Controller\Customer;

use Dotdigitalgroup\Email\Helper\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Contact;
use Dotdigitalgroup\Email\Model\ContactFactory;
use Dotdigitalgroup\Email\Model\Customer\Account\Configuration;
use Laminas\Http\Headers;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
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
     * @var ContactFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockContactFactory;

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
        $this->mockContactFactory = $this->createMock(ContactFactory::class);
        $this->mockCustomer = $this->createMock(Customer::class);
        $this->mockHelper = $this->createMock(Data::class);
        $this->mockAccountConfig = $this->createMock(Configuration::class);
        $this->mockClient = $this->createMock(Client::class);
        $this->mockStore = $this->createMock(Store::class);
        $this->mockStoreManager = $this->createMock(StoreManagerInterface::class);

        $sessionMethods = array_merge(get_class_methods(CustomerSession::class), ['getConnectorContactId']);
        $this->mockCustomerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods($sessionMethods)
            ->getMock();

        $this->mockECContactObject = (object) ['id' => '111'];

        $objectManager->addSharedInstance($this->mockFormKeyValidator, FormKeyValidator::class);
        $objectManager->addSharedInstance($this->mockCustomerSession, CustomerSession::class);
        $objectManager->addSharedInstance($this->mockHelper, Data::class);
        $objectManager->addSharedInstance($this->mockContactFactory, ContactFactory::class);
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

        $this->mockClient->expects($this->exactly(2))
            ->method('postAddressBookContacts')
            ->withConsecutive(
                [1, $this->mockECContactObject],
                [2, $this->mockECContactObject]
            );

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

        $this->dispatch('/connector/customer/newsletter');
    }

    private function setUpSharedMocks()
    {
        $contactId = 12;
        $websiteId = 1;

        $this->mockFormKeyValidator->method('validate')
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

        $this->mockContactFactory->method('create')
            ->willReturn($this->mockContact);

        $this->mockContact->method('loadByCustomerEmail')
            ->willReturn($this->mockContact);

        $this->mockCustomerSession->method('getConnectorContactId')
            ->willReturn($contactId);

        $this->mockHelper->method('getWebsiteApiClient')
            ->willReturn($this->mockClient);

        $this->mockClient->method('getContactById')
            ->willReturn($this->mockECContactObject);
    }
}
