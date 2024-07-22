<?php

namespace Dotdigitalgroup\Email\Test\Integration\Sync;

use Dotdigitalgroup\Email\Helper\Config;
use Dotdigitalgroup\Email\Helper\File;
use Dotdigitalgroup\Email\Model\Customer\CustomerDataFieldProviderFactory;
use Dotdigitalgroup\Email\Model\Importer;
use Dotdigitalgroup\Email\Model\ResourceModel\Contact\CollectionFactory as ContactCollectionFactory;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\Collection;
use Dotdigitalgroup\Email\Model\ResourceModel\Importer\CollectionFactory;
use Dotdigitalgroup\Email\Model\Sync\Customer;
use Dotdigitalgroup\Email\Model\Sync\Customer\Exporter;
use Dotdigitalgroup\Email\Setup\Install\Type\InsertEmailContactTableCustomers;
use Dotdigitalgroup\Email\Test\Integration\MocksApiResponses;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

class CustomerSyncTest extends \PHPUnit\Framework\TestCase
{
    use MocksApiResponses;

    /**
     * @var \Dotdigitalgroup\Email\Helper\Data
     */
    private $helper;

    /**
     * @var CustomerDataFieldProviderFactory
     */
    private $customerDataFieldProviderFactory;

    /**
     * @var Customer
     */
    private $customerSync;

    /**
     * @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact
     */
    private $contactResource;

    /**
     * @var Importer
     */
    private $importer;

    /**
     * @var File
     */
    private $fileHelper;

    /**
     * @var DriverInterface
     */
    private $fileSystem;

    /**
     * @var CollectionFactory
     */
    private $importerCollectionFactory;

    /**
     * @var ContactCollectionFactory
     */
    private $contactCollectionFactory;

    /**
     * @var Exporter
     */
    private $exporter;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp() :void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->importerCollectionFactory = $this->objectManager->create(CollectionFactory::class);
        $this->contactCollectionFactory = $this->objectManager->create(ContactCollectionFactory::class);
        $this->customerDataFieldProviderFactory = $this->objectManager->create(CustomerDataFieldProviderFactory::class);
        $this->exporter = $this->objectManager->create(Exporter::class);

        $this->cleanTables();

        $this->mockClientFactory();
        $this->setApiConfigFlags();
        $this->setExtraConfigForTest();

        $this->helper = $this->instantiateDataHelper();

        $this->customerSync = $this->objectManager->create(Customer::class);
        $this->importer = $this->objectManager->create(Importer::class);
        $this->fileHelper = $this->objectManager->create(File::class);
        $this->fileSystem = $this->objectManager->create(DriverInterface::class);
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/five_repository_customers.php
     */
    public function testContactExportBulkImportQueued()
    {
        $this->customerSync->sync();
        $contactsQueue = $this->getContactImports();

        $this->assertEquals(1, $contactsQueue['totalRecords']);
        $this->assertEquals(
            Importer::IMPORT_TYPE_CUSTOMER,
            end($contactsQueue['items'])['import_type'],
            'Item is not of type contact'
        );
        $this->assertEquals(
            Importer::MODE_BULK,
            end($contactsQueue['items'])['import_mode'],
            'Item is not in bulk mode'
        );
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/five_repository_customers.php
     */
    public function testContactExportJsonContainsContacts()
    {
        // get the contacts we expect to be exported
        $websiteId = 1;
        $contactsToExport = $this->contactCollectionFactory->create()
            ->getCustomersToImportByWebsite(
                $websiteId,
                $this->helper->isOnlySubscribersForContactSync($websiteId),
                5,
                0
            )->toArray();

        $this->customerSync->sync();
        $contactsQueue = $this->getContactImports();
        $json = end($contactsQueue['items'])['import_data'];

        foreach ($contactsToExport['items'] as $contact) {
            $this->assertStringContainsString($contact['email'], $json);
        }
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/five_repository_customers.php
     */
    public function testExportContainsExpectedColumns()
    {
        $this->customerSync->sync();
        $contactsQueue = $this->getContactImports();

        $this->exporter->setFieldMapping($this->helper->getWebsiteById(1));
        $mappedDataFields = $this->exporter->getFieldMapping();

        $json = json_decode(end($contactsQueue['items'])['import_data']);

        foreach ($json as $exportedCustomer) {
            foreach ($exportedCustomer->dataFields as $key => $field) {
                $this->assertContains($key, $mappedDataFields);
            }
        }
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/five_repository_customers.php
     */
    public function testCsvContainsCustomAttributeColumns()
    {
        /** @var Json $serializer */
        $serializer = $this->objectManager->create(Json::class);
        $this->setApiConfigFlags([
            Config::XML_PATH_CONNECTOR_MAPPING_CUSTOM_DATAFIELDS => $serializer->serialize([[
                'attribute' => 'downward_trend',
                'datafield' => 'DOWNWARD_TREND',
            ], [
                'attribute' => 'chatty_console',
                'datafield' => 'CHATTY_CONSOLE',
            ]]),
        ]);

        $this->customerSync->sync();
        $contactsQueue = $this->getContactImports();
        $json = $serializer->unserialize(end($contactsQueue['items'])['import_data']);

        $values = array_values($json);
        $exportedCustomer = $values[0];

        $dataFieldKeys = array_keys($exportedCustomer['dataFields']);
        $this->assertContains('DOWNWARD_TREND', $dataFieldKeys);
        $this->assertContains('CHATTY_CONSOLE', $dataFieldKeys);
    }

    /**
     * Get contact imports.
     *
     * Because sync now pushes data to Dotdigital, this send fails in the context of the test.
     * However, failed imports are still added to the table. Hence we check the failed items.
     *
     * @return array
     */
    private function getContactImports()
    {
        /** @var Collection $importerCollection */
        $importerCollection = $this->importerCollectionFactory->create();
        $importerCollection->addFieldToSelect(['import_type', 'import_data', 'import_mode'])
            ->addFieldToFilter('import_status', 3)
            ->addFieldToFilter('website_id', ['in' => [1]]);

        return $importerCollection->toArray();
    }

    /**
     * Clear all data from contact and importer tables and migrate contacts
     */
    private function cleanTables()
    {
        /** @var \Dotdigitalgroup\Email\Model\ResourceModel\Importer $importerResource */
        $importerResource = $this->objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Importer::class);
        /** @var \Dotdigitalgroup\Email\Model\ResourceModel\Contact $contactResource */
        $contactResource = $this->objectManager->create(\Dotdigitalgroup\Email\Model\ResourceModel\Contact::class);

        foreach ($this->importerCollectionFactory->create()->getItems() as $item) {
            $importerResource->delete($item);
        }
        foreach ($this->contactCollectionFactory->create()->getItems() as $item) {
            $contactResource->delete($item);
        }

        ObjectManager::getInstance()->create(InsertEmailContactTableCustomers::class)->execute();
    }

    /**
     * @return void
     */
    private function setExtraConfigForTest()
    {
        foreach ([
             Config::XML_PATH_CONNECTOR_SYNC_CUSTOMER_ENABLED => 1,
             Config::XML_PATH_CONNECTOR_CUSTOMERS_ADDRESS_BOOK_ID => 'customers called chaz',
             Config::XML_PATH_CONNECTOR_SYNC_ALLOW_NON_SUBSCRIBERS => 1,
             Config::XML_PATH_CONNECTOR_MAPPING_CUSTOMER_ID => "CUSTOMER_ID",
             Config::XML_PATH_CONNECTOR_CUSTOMER_FIRSTNAME => "FIRSTNAME",
             Config::XML_PATH_CONNECTOR_CUSTOMER_LASTNAME => "LASTNAME",
                 ] as $path => $value) {
            $this->getMutableScopeConfig()->setValue($path, $value, ScopeInterface::SCOPE_WEBSITE);
        }
    }
}
