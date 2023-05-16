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
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp() :void
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->importerCollectionFactory = $this->objectManager->create(CollectionFactory::class);
        $this->contactCollectionFactory = $this->objectManager->create(ContactCollectionFactory::class);
        $this->customerDataFieldProviderFactory = $this->objectManager->create(CustomerDataFieldProviderFactory::class);

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
        $contactsQueue = $this->getContactImporterQueue();
        $website = $this->helper->getWebsiteById(1);

        $this->assertEquals(1, $contactsQueue['totalRecords']);
        $this->assertStringContainsString(
            sprintf(
                '%s_customers_%s',
                $website->getCode(),
                (new \DateTime('now', new \DateTimeZone('UTC')))->format('d_m_Y')
            ),
            end($contactsQueue['items'])['import_file']
        );
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
    public function testContactExportCsvFileExists()
    {
        // To ensure the import filename changes between tests
        sleep(1);

        $this->customerSync->sync();
        $contactsQueue = $this->getContactImporterQueue();

        $this->assertFileExists($this->fileHelper->getFilePath(end($contactsQueue['items'])['import_file']));
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/five_repository_customers.php
     */
    public function testContactExportCsvContainsContacts()
    {
        // To ensure the import filename changes between tests
        sleep(1);

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
        $contactsQueue = $this->getContactImporterQueue();
        $csv = $this->getCsvContent(end($contactsQueue['items'])['import_file']);

        foreach ($contactsToExport['items'] as $contact) {
            $this->assertTrue(
                in_array($contact['email'], array_column($csv, 'Email'))
            );
        }
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/five_repository_customers.php
     */
    public function testCsvExportContainsExpectedColumns()
    {
        // To ensure the import filename changes between tests
        sleep(1);

        $this->customerSync->sync();
        $contactsQueue = $this->getContactImporterQueue();
        $dataFields = $this->getMappedDataFields();
        $csv = $this->getCsvContent(end($contactsQueue['items'])['import_file']);

        foreach ($dataFields as $field) {
            $this->assertContains(
                $field,
                array_keys(reset($csv))
            );
        }
    }

    /**
     * @magentoDataFixture Magento/Customer/_files/five_repository_customers.php
     */
    public function testCsvContainsCustomAttributeColumns()
    {
        // To ensure the import filename changes between tests
        sleep(1);

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
        $contactsQueue = $this->getContactImporterQueue();
        $csv = $this->getCsvContent(end($contactsQueue['items'])['import_file']);

        $this->assertContains('DOWNWARD_TREND', array_keys(reset($csv)));
        $this->assertContains('CHATTY_CONSOLE', array_keys(reset($csv)));
    }

    /**
     * Load the generated CSV
     *
     * @param string $fileName
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getCsvContent(string $fileName)
    {
        $resource = $this->fileSystem->fileOpen(
            $this->fileHelper->getFilePath($fileName),
            'r'
        );

        $csvRow = 0;
        $rowHeaders = [];
        $csv = [];
        while ($result = $this->fileSystem->fileGetCsv($resource)) {
            if ($csvRow++ === 0) {
                $rowHeaders = $result;
                continue;
            }
            $csv[] = array_combine($rowHeaders, $result);
        }
        $this->fileSystem->fileClose($resource);

        return $csv;
    }

    /**
     * @return array
     */
    private function getContactImporterQueue()
    {
        /** @var Collection $importerCollection */
        $importerCollection = $this->importerCollectionFactory->create();
        return $importerCollection->getQueueByTypeAndMode(
            Importer::IMPORT_TYPE_CUSTOMER,
            Importer::MODE_BULK,
            1,
            [1]
        )->toArray();
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getMappedDataFields()
    {
        return $this->customerDataFieldProviderFactory
            ->create(['data' => ['website' => $this->helper->getWebsiteById(1)]])
            ->getCustomerDataFields();
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
