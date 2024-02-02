<?php

namespace Dotdigitalgroup\Email\Test\Integration\AbandonedCarts;

if (!class_exists('\Magento\Catalog\Api\Data\ProductExtensionInterfaceFactory')) {
    require __DIR__ . '/../_files/product_extension_interface_hacktory.php';
}

use Dotdigital\V3\Models\Contact;
use Dotdigital\V3\Models\Contact\ChannelProperty;
use Dotdigital\V3\Models\Contact\ChannelProperties\EmailChannelProperty;
use Dotdigitalgroup\Email\Model\Abandoned;
use Dotdigitalgroup\Email\Model\AbandonedCart\CartInsight\Data;
use Dotdigitalgroup\Email\Model\Apiconnector\Client;
use Dotdigitalgroup\Email\Model\Apiconnector\V3\Contact\Patcher;
use Dotdigitalgroup\Email\Model\ResourceModel\Abandoned as AbandonedResource;
use Dotdigitalgroup\Email\Model\ResourceModel\Abandoned\Collection as AbandonedCollection;
use Dotdigitalgroup\Email\Model\Sales\Quote;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Quote\Model\ResourceModel\Quote\Collection;
use PHPUnit\Framework\TestCase;

class CustomerGuestAbandonedTest extends TestCase
{
    use MocksApiResponses;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    public $quote;

    /**
     * @var \Dotdigitalgroup\Email\Test\Integration\AbandonedCarts\Fixture
     */
    public $fixture;

    /**
     * @return void
     */
    public function setUp() :void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $this->setApiConfigFlags();

        $this->quote = $this->objectManager->create(\Magento\Quote\Model\Quote::class);
        $this->fixture = $this->objectManager->create(Fixture::class);
        $this->loadCustomerQuoteTextureFile();

        $cartInsightMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $cartInsightMock->method('send')->willReturn(true);
        $this->objectManager->addSharedInstance($cartInsightMock, Data::class);

        $mockClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->setMethods(get_class_methods(Client::class))
            ->getMock();
        $mockClient->method('setApiUsername')
            ->willReturn(new class() {
                public function setApiPassword($password)
                {
                }
            });
        $mockClient->method('getContactByEmail')
            ->willReturn((object) [
                'id' => 1234566,
                'status' => "Subscribed",
            ]);

        $clientFactoryClass = 'Dotdigitalgroup\Email\Model\Apiconnector\ClientFactory';
        $mockClientFactory = $this->getMockBuilder($clientFactoryClass)
            ->disableOriginalConstructor()
            ->getMock();
        $mockClientFactory->method('create')->willReturn($mockClient);

        // share a pre-generated data helper with the mock factory
        $this->instantiateDataHelper([
            $clientFactoryClass => $mockClientFactory,
        ]);

        // add mocks for classes related to V3 Client (because authentication won't work in an integration test)
        $contactResponseMock = $this->getMockBuilder(Contact::class)
            ->disableOriginalConstructor()
            ->addMethods(['getChannelProperties'])
            ->getMock();
        $channelPropertyMock = $this->getMockBuilder(ChannelProperty::class)
            ->disableOriginalConstructor()
            ->addMethods(['getEmail'])
            ->getMock();
        $emailChannelPropertyMock = $this->createMock(EmailChannelProperty::class);
        $v3PatcherMock = $this->createMock(Patcher::class);
        $v3PatcherMock->method('getOrCreateContactByEmail')->willReturn($contactResponseMock);
        $contactResponseMock->method('getChannelProperties')->willReturn($channelPropertyMock);
        $channelPropertyMock->method('getEmail')->willReturn($emailChannelPropertyMock);
        $this->objectManager->addSharedInstance($v3PatcherMock, Patcher::class);
    }

    public function tearDown() :void
    {
        $abandonedCollection = $this->objectManager->create(
            AbandonedCollection::class
        );
        $abandonedCollection->walk('delete');
        $quoteCollection = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
        $quoteCollection->walk('delete');
    }

    /**
     * @magentoConfigFixture default_store connector_configuration/abandoned_carts/allow_non_subscribers 1
     * @magentoConfigFixture default_store abandoned_carts/customers/enabled_1 1
     * @magentoConfigFixture default_store abandoned_carts/customers/send_after_1 0
     * @magentoConfigFixture default_store abandoned_carts/customers/campaign_1 1234
     */
    public function testCustomerAbandonedCartOne()
    {
        $numExpectedAC = 1;
        //create a quote for customer for AC 1
        $quoteCollection = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
        $quote = $quoteCollection->getFirstItem();
        $quoteId = $quote->getId();
        $storeId = $quote->getStoreId();
        $this->quote->loadActive($quoteId);

        /**
         * run the cron for abandoned carts
         *
         * abandoned cart name => number of carts
         * @var Quote $emailQuote
         */
        $emailQuote = $this->objectManager->create(Quote::class);
        $result = $emailQuote->processAbandonedCarts();

        $this->assertEquals(
            $numExpectedAC,
            $result[$storeId]['firstCustomer'],
            'Abandoned cart was not found for store : ' . $storeId . ', quote id : ' . $quoteId
        );
    }

    /**
     * @magentoConfigFixture default_store abandoned_carts/customers/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/customers/send_after_2 1
     * @magentoConfigFixture default_store abandoned_carts/customers/send_after_1 15
     * @magentoConfigFixture default_store abandoned_carts/customers/campaign_2 1234
     * @magentoConfigFixture default_store connector_api_credentials/api/endpoint https://r1-api.dotdigital.com
     * @magentoConfigFixture default_store connector_configuration/abandoned_carts/allow_non_subscribers 1
     */
    public function testExistingCustomerAbandonedCart()
    {
        $sendAfter = '1';
        $abandoned = $this->createExistingAbandonedCart($sendAfter);
        $storeId = $abandoned->getStoreId();

        $emailQuote = $this->objectManager->create(Quote::class);
        $result = $emailQuote->processAbandonedCarts();

        $this->assertEquals(1, $result[$storeId]['secondCustomer']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote_with_bundle.php
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_1 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_1 0
     * @magentoConfigFixture default_store abandoned_carts/guests/campaign_1 1234
     * @magentoConfigFixture default_store connector/api/endpoint https://r1-api.dotmailer.com
     * @magentoConfigFixture default_store connector_configuration/abandoned_carts/allow_non_subscribers 1
     */
    public function testGuestAbandonedCartOne()
    {
        $email = 'test@test.magento.com';
        $quoteCollection = $this->objectManager->create(\Magento\Quote\Model\ResourceModel\Quote\Collection::class);
        $quoteCollection->addFieldToFilter('customer_is_guest', true)
            ->addFieldToFilter('customer_email', $email)
            ->getFirstItem();
        $emailQuote = $this->objectManager->create(Quote::class);
        $emailQuote->processAbandonedCarts();
    }

    /**
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_3 2
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_1 15
     * @magentoConfigFixture default_store abandoned_carts/guests/campaign_2 1234
     * @magentoConfigFixture default_store connector_configuration/abandoned_carts/allow_non_subscribers 1
     */
    public function testExistingAbandonedCartGuest()
    {
        $abandoned = $this->createExistingAbandonedCartGuest(1);
        $storeId = $abandoned->getStoreId();

        /** @var Quote $emailQuote */
        $emailQuote = $this->objectManager->create(Quote::class);
        $result = $emailQuote->processAbandonedCarts();

        $this->assertEquals(1, $result[$storeId]['secondGuest']);
    }

    /**
     * @magentoConfigFixture default_store abandoned_carts/guests/enabled_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_3 2
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_2 1
     * @magentoConfigFixture default_store abandoned_carts/guests/send_after_1 15
     * @magentoConfigFixture default_store abandoned_carts/guests/campaign_2 1234
     * @magentoConfigFixture default_store connector_configuration/abandoned_carts/allow_non_subscribers 1
     */
    public function testExistingGuestAbandonedCartItemsChanged()
    {
        $abandoned = $this->createExistingAbandonedCartGuest(1, [45,34], 1);
        $storeId = $abandoned->getStoreId();

        /** @var Quote $emailQuote */
        $emailQuote = $this->objectManager->create(Quote::class);
        $result = $emailQuote->processAbandonedCarts();

        $this->assertEquals(0, $result[$storeId]['secondGuest']);
    }

    /**
     * @param $hour
     *
     * @return Abandoned
     * @throws AlreadyExistsException
     */
    private function createExistingAbandonedCart($hour)
    {
        /** @var Collection $quoteCollection */
        $quoteCollection = $this->objectManager->create(Collection::class);
        $quote = $quoteCollection->getFirstItem();
        $quote->save();

        $quoteUpdatedAt = new \DateTime('now', new \DateTimezone('UTC'));
        $quoteUpdatedAt->sub(\DateInterval::createFromDateString($hour . ' hours + 1 minutes'));

        return $this->setAbandonedModelProperties(
            $quote,
            !empty($itemIds) ? $itemIds : $this->getQuoteItemIds($quote),
            $quoteUpdatedAt
        );
    }

    /**
     * @param $hour
     * @param array $itemIds
     * @param null $updateQuoteItemCount
     *
     * @return Abandoned
     * @throws AlreadyExistsException
     */
    private function createExistingAbandonedCartGuest(
        $hour,
        $itemIds = [],
        $updateQuoteItemCount = null
    ) {
        /** @var Collection $quoteCollection */
        $quoteCollection = $this->objectManager->create(Collection::class);
        $quote = $quoteCollection->getFirstItem();
        $quote->setCustomerId(null);
        $quote->setCustomerIsGuest('1');

        if ($updateQuoteItemCount) {
            $quote->setItemsCount($updateQuoteItemCount);
        }
        $quote->save();

        $quoteUpdatedAt = new \DateTime('now', new \DateTimezone('UTC'));
        $quoteUpdatedAt->sub(\DateInterval::createFromDateString($hour . ' hours + 1 minutes'));

        return $this->setAbandonedModelProperties(
            $quote,
            !empty($itemIds) ? $itemIds : $this->getQuoteItemIds($quote),
            $quoteUpdatedAt
        );
    }

    private function getQuoteItemIds($quote)
    {
        $itemIds = [];
        foreach ($quote->getItemsCollection() as $item) {
            $itemIds[] = $item->getProductId();
        }
        return $itemIds;
    }

    /**
     * @param $quote
     * @param array $itemIds
     * @param $quoteUpdatedAt
     * @return Abandoned
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    private function setAbandonedModelProperties($quote, array $itemIds, $quoteUpdatedAt)
    {
        /** @var Abandoned $abandonedModel */
        $abandonedModel = $this->objectManager->create(Abandoned::class);
        $abandonedModel->setQuoteId($quote->getId())
            ->setIsActive(1)
            ->setItemsCount(count($itemIds))
            ->setQuoteId($quote->getId())
            ->setItemsIds(implode(',', $itemIds))
            ->setCreatedAt($updated = (new \DateTime('now', new \DateTimezone('UTC')))->getTimestamp())
            ->setAbandonedCartNumber(1)
            ->setStoreId($quote->getStoreId())
            ->setQuoteUpdatedAt($quoteUpdatedAt)
            ->setStatus('Sent')
            ->setCustomerId($quote->getCustomerId())
            ->setEmail($quote->getCustomerEmail())
            ->setUpdatedAt($updated);

        $resourceAbandoned = $this->objectManager->create(AbandonedResource::class);
        $resourceAbandoned->save($abandonedModel);

        return $abandonedModel;
    }

    private function loadCustomerQuoteTextureFile()
    {
        include __DIR__ . '/../_files/customer.php';
        $this->fixture->createQuote($this->objectManager, 1, 'dottest01', true);
    }
}
